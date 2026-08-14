<?php
namespace App\Controllers\Api;

use App\AdminPath;
use Core\Auth;
use Core\Controller;
use Core\Database;

class NotificationController extends Controller
{
    public function listApi(): void
    {
        if (!$this->requireAuthApi()) {
            return;
        }
        $userId = Auth::id();
        $stmt = Database::getInstance()->prepare('
            SELECT id, type, related_type, related_id, message, clicked_at, created_at
            FROM notifications
            WHERE user_id = ? AND clicked_at IS NULL
            ORDER BY created_at DESC
            LIMIT 50
        ');
        $stmt->execute([$userId]);
        $items = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $row) {
            $items[] = [
                'id' => (int) $row->id,
                'type' => $row->type,
                'related_type' => $row->related_type,
                'related_id' => (int) $row->related_id,
                'message' => $row->message,
                'created_at' => $row->created_at,
                'url' => AdminPath::url('notifications/click/' . (int) $row->id),
            ];
        }
        $this->apiSuccess($items);
    }
}
