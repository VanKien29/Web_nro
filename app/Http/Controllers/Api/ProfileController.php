<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game\HeadAvatar;
use App\Models\Game\TaskMainTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $account = $request->get('game_user');
        $player = $account->player;

        if (!$player) {
            return response()->json([
                'ok' => true,
                'data' => [
                    'user' => [
                        'id' => (int) $account->id,
                        'username' => $account->username,
                        'email' => $account->email ?? null,
                        'cash' => (int) $account->cash,
                        'danap' => (int) $account->danap,
                        'active' => (int) $account->active,
                    ],
                    'player' => [
                        'has_character' => false,
                    ],
                ],
            ]);
        }

        // Sức mạnh
        $power = $player->power;

        // Nhiệm vụ
        $taskName = 'Chưa có nhiệm vụ';
        $taskData = $player->task_data;
        if (!empty($taskData[0])) {
            $task = TaskMainTemplate::find($taskData[0]);
            if ($task) {
                $taskName = $task->name;
            }
        }

        // Giới tính
        $genderText = match ((int) $player->gender) {
            0 => 'Trái Đất',
            1 => 'Namec',
            2 => 'Xayda',
            default => 'Không xác định',
        };

        // Avatar
        $avatarUrl = '/assets/frontend/home/v1/images/bannergame.png';
        if (!empty($player->head)) {
            $headAvatar = HeadAvatar::where('head_id', $player->head)->first();
            if ($headAvatar) {
                $avatarUrl = '/assets/frontend/home/v1/images/x4/' . $headAvatar->avatar_id . '.png';
            }
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'user' => [
                    'username' => $account->username,
                    'email' => $account->email ?? null,
                    'cash' => (int) ($account->cash ?? 0),
                    'danap' => (int) ($account->danap ?? 0),
                    'active' => (int) ($account->active ?? 0),
                ],
                'player' => [
                    'has_character' => true,
                    'name' => $player->name,
                    'power' => $power,
                    'task_name' => $taskName,
                    'gender_text' => $genderText,
                    'avatar_url' => $avatarUrl,
                ],
            ],
        ]);
    }
}
