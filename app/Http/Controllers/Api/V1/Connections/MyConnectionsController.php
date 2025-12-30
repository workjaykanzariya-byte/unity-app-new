<?php

namespace App\Http\Controllers\Api\V1\Connections;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Connection\ConnectionUserResource;
use App\Models\Connection;
use Illuminate\Http\Request;

class MyConnectionsController extends BaseApiController
{
    public function index(Request $request)
    {
        $authUser = $request->user();

        $connections = Connection::query()
            ->with([
                'requester.profilePhotoFile',
                'addressee.profilePhotoFile',
            ])
            ->where('is_approved', true)
            ->where(function ($query) use ($authUser) {
                $query->where('requester_id', $authUser->id)
                    ->orWhere('addressee_id', $authUser->id);
            })
            ->orderByDesc('created_at')
            ->get();

        $items = $connections->map(function (Connection $connection) use ($authUser) {
            $otherUser = $connection->requester_id === $authUser->id
                ? $connection->addressee
                : $connection->requester;

            if (! $otherUser || $otherUser->id === $authUser->id) {
                return null;
            }

            return [
                'connected_at' => $connection->created_at,
                'user' => new ConnectionUserResource($otherUser),
            ];
        })->filter()->values();

        return $this->success([
            'items' => $items,
        ]);
    }
}
