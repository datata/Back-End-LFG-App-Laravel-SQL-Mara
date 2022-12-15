<?php

namespace App\Http\Controllers;

use App\Models\Party;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PartyController extends Controller
{
    public function createParty(Request $req)
    {
        try {
            $userId = auth()->user()->id;

            $party = Party::create([
                'name' => $req->get('name'),
                'game_id' => $req->get('game_id')
            ]);

            $party->user()->attach($userId, ['owner' => true, 'active' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Party created',
            ]);
        } catch (\Throwable $th) {
            Log::error("Error creating party: " . $th->getMessage());

            return response()->json([
                'success' => true,
                'message' => 'Party could not be created'
            ], 500);
        }
    }

    public function joinPartyById($id)
    {
        try {
            $userId = auth()->user()->id;
            $party = Party::find($id);
            $active = $party->user()->wherePivot('active', true)->find($userId);
            $existing = $party->user()->find($userId);
            if ($active) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already are in that party',
                ]);
            } else if ($existing) {
                $party->user()->updateExistingPivot($userId, ['owner' => false, 'active' => true]);
                return response()->json([
                    'success' => true,
                    'message' => 'Party re-joined',
                ]);
            } else {
                $party->user()->attach($userId, ['owner' => false, 'active' => true]);
                return response()->json([
                    'success' => true,
                    'message' => 'Party joined',
                ]);
            }
        } catch (\Throwable $th) {
            Log::error("Error joining party: " . $th->getMessage());

            return response()->json([
                'success' => true,
                'message' => 'Could not join party'
            ], 500);
        }
    }

    public function leaveParty($id)
    {
        try {
            $userId = auth()->user()->id;
            $party = Party::find($id);
            $owner = $party->user()->wherePivot('owner', true)->first();
            if ($owner->id == $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'The owner cannot leave the party, delete it instead'
                ]);
            } else {
                $party->user()->updateExistingPivot($userId, ['owner' => false, 'active' => false]);
                return response()->json([
                    'success' => true,
                    'message' => 'You have left the party',
                ]);
            }
        } catch (\Throwable $th) {
            Log::error("Error joining party: " . $th->getMessage());

            return response()->json([
                'success' => true,
                'message' => 'Could not leave party'
            ], 500);
        }
    }
}
