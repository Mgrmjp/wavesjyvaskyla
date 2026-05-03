<?php

class RevisionLog
{
    private static string $file;
    private static int $maxRevisions = 50;

    public static function init(string $dataDir): void
    {
        self::$file = $dataDir . '/revisions.json';
        if (!is_file(self::$file)) {
            file_put_contents(self::$file, json_encode(['revisions' => []], JSON_PRETTY_PRINT));
        }
    }

    public static function log(string $entity, string $action, array $data, ?array $before = null): void
    {
        $log = self::load();
        $revision = [
            'id' => bin2hex(random_bytes(8)),
            'timestamp' => date('c'),
            'entity' => $entity,
            'action' => $action,
            'data' => $data,
        ];
        if ($before !== null) {
            $revision['before'] = $before;
        }
        array_unshift($log['revisions'], $revision);
        $log['revisions'] = array_slice($log['revisions'], 0, self::$maxRevisions);
        file_put_contents(self::$file, json_encode($log, JSON_PRETTY_PRINT));
    }

    public static function getRevisions(?string $entity = null, int $limit = 50): array
    {
        $log = self::load();
        $revisions = $log['revisions'];
        if ($entity !== null) {
            $revisions = array_values(array_filter($revisions, fn($r) => ($r['entity'] ?? '') === $entity));
        }
        return array_slice($revisions, 0, $limit);
    }

    public static function getRevision(string $id): ?array
    {
        $log = self::load();
        foreach ($log['revisions'] as $r) {
            if (($r['id'] ?? '') === $id) return $r;
        }
        return null;
    }

    public static function restore(string $id): bool
    {
        $revision = self::getRevision($id);
        if ($revision === null) return false;

        $entity = $revision['entity'] ?? '';
        $data = $revision['data'] ?? null;
        if ($entity === '' || $data === null) return false;

        $storeFile = self::getStoreFile($entity);
        if ($storeFile === null) return false;

        file_put_contents($storeFile, json_encode($data, JSON_PRETTY_PRINT));
        return true;
    }

    public static function deleteRevision(string $id): bool
    {
        $log = self::load();
        $before = count($log['revisions']);
        $log['revisions'] = array_values(array_filter($log['revisions'], fn($r) => ($r['id'] ?? '') !== $id));
        if (count($log['revisions']) < $before) {
            file_put_contents(self::$file, json_encode($log, JSON_PRETTY_PRINT));
            return true;
        }
        return false;
    }

    private static function load(): array
    {
        if (!is_file(self::$file)) return ['revisions' => []];
        $content = file_get_contents(self::$file);
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : ['revisions' => []];
    }

    private static function getStoreFile(string $entity): ?string
    {
        $map = [
            'menu' => 'menu.json',
            'settings' => 'settings.json',
        ];
        $filename = $map[$entity] ?? null;
        if ($filename === null) return null;
        $dataDir = dirname(self::$file);
        $path = $dataDir . '/' . $filename;
        return is_file($path) ? $path : null;
    }
}
