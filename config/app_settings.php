<?php
// Single source of truth for public school/contact/social settings.

function app_settings_defaults() {
    return [
        'school_name' => 'Kidzenia Kindergarten',
        'school_address' => '123 Education Street, Learning City',
        'school_phone' => '+91 9876543210',
        'school_email' => 'hello@kidzenia.com',
        'academic_year' => '2024-2025',
        'facebook_url' => '',
        'twitter_url' => '',
        'instagram_url' => '',
        'youtube_url' => '',
        'linkedin_url' => ''
    ];
}

function app_settings_keys() {
    return array_keys(app_settings_defaults());
}

function load_app_settings(PDO $db) {
    migrate_social_settings_from_homepage_cms($db);

    $settings = app_settings_defaults();
    $placeholders = implode(',', array_fill(0, count(app_settings_keys()), '?'));
    $query = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)";
    $stmt = $db->prepare($query);
    $stmt->execute(app_settings_keys());

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    return $settings;
}

function save_app_settings(PDO $db, array $settings) {
    $allowedKeys = app_settings_keys();
    $query = "INSERT INTO settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)
              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
    $stmt = $db->prepare($query);

    foreach ($settings as $key => $value) {
        if (!in_array($key, $allowedKeys, true)) {
            continue;
        }

        $stmt->execute([
            ':setting_key' => $key,
            ':setting_value' => in_array($key, ['school_phone', 'school_email'], true)
                ? normalize_multi_setting($value)
                : trim((string)$value)
        ]);
    }
}

function normalize_multi_setting($value) {
    return implode(PHP_EOL, app_setting_list((string)$value));
}

function app_setting_list($value) {
    if (is_array($value)) {
        $value = implode(PHP_EOL, $value);
    }

    $items = preg_split('/[\r\n,;]+/', (string)$value);
    $items = array_map('trim', $items);
    $items = array_filter($items, static function ($item) {
        return $item !== '';
    });

    return array_values(array_unique($items));
}

function app_phone_link($phone) {
    return preg_replace('/[^0-9+]/', '', $phone);
}

function migrate_social_settings_from_homepage_cms(PDO $db) {
    static $migrated = false;
    if ($migrated) {
        return;
    }
    $migrated = true;

    try {
        $socialKeys = ['facebook_url', 'twitter_url', 'instagram_url', 'youtube_url', 'linkedin_url'];
        $placeholders = implode(',', array_fill(0, count($socialKeys), '?'));

        $settingsQuery = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)";
        $settingsStmt = $db->prepare($settingsQuery);
        $settingsStmt->execute($socialKeys);
        $existingSettings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $cmsQuery = "SELECT content_key, content_value FROM homepage_cms
                     WHERE section = 'footer' AND content_key IN ($placeholders)";
        $cmsStmt = $db->prepare($cmsQuery);
        $cmsStmt->execute($socialKeys);

        $toSave = [];
        foreach ($cmsStmt->fetchAll(PDO::FETCH_KEY_PAIR) as $key => $value) {
            if (($existingSettings[$key] ?? '') === '' && trim((string)$value) !== '') {
                $toSave[$key] = $value;
            }
        }

        if ($toSave) {
            save_app_settings($db, $toSave);
        }
    } catch (PDOException $exception) {
        // Older installs may not have homepage_cms yet. Settings still work.
    }
}
?>
