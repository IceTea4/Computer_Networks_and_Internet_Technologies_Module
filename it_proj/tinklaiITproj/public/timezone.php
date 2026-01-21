<?php
/**
 * Timezone Helper Functions
 * Handles conversion between UTC (server/database) and user's local timezone
 */

/**
 * Get user's timezone from cookie, defaults to Europe/Vilnius
 * @return string Timezone identifier (e.g., 'Europe/Vilnius', 'America/New_York')
 */
function getUserTimezone() {
    return isset($_COOKIE['user_timezone']) ? $_COOKIE['user_timezone'] : 'Europe/Vilnius';
}

/**
 * Convert UTC datetime string to user's local timezone
 * @param string $utcDatetimeStr UTC datetime string (e.g., '2025-01-15 10:30:00')
 * @param string $format Output format (default: 'Y-m-d H:i')
 * @return string Formatted datetime in user's timezone
 */
function utcToLocal($utcDatetimeStr, $format = 'Y-m-d H:i') {
    if (empty($utcDatetimeStr)) {
        return '';
    }

    try {
        $utcTimezone = new DateTimeZone('UTC');
        $userTimezone = new DateTimeZone(getUserTimezone());

        $datetime = new DateTime($utcDatetimeStr, $utcTimezone);
        $datetime->setTimezone($userTimezone);

        return $datetime->format($format);
    } catch (Exception $e) {
        // Fallback: return original string if conversion fails
        return $utcDatetimeStr;
    }
}

/**
 * Convert user's local datetime to UTC for database storage
 * @param string $localDatetimeStr Local datetime string (e.g., '2025-01-15 10:30:00' or '2025-01-15T10:30')
 * @return string UTC datetime string in format 'Y-m-d H:i:s'
 */
function localToUtc($localDatetimeStr) {
    if (empty($localDatetimeStr)) {
        return '';
    }

    try {
        $userTimezone = new DateTimeZone(getUserTimezone());
        $utcTimezone = new DateTimeZone('UTC');

        // Handle datetime-local format (with 'T')
        $localDatetimeStr = str_replace('T', ' ', $localDatetimeStr);

        $datetime = new DateTime($localDatetimeStr, $userTimezone);
        $datetime->setTimezone($utcTimezone);

        return $datetime->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        // Fallback: return original string if conversion fails
        return $localDatetimeStr;
    }
}

/**
 * Initialize user timezone from browser
 * Call this once when user first visits the site
 * Outputs JavaScript to detect and store timezone
 */
function initializeUserTimezone() {
    // Only output JavaScript if timezone cookie is not set
    if (!isset($_COOKIE['user_timezone'])) {
        echo <<<'HTML'
<script>
(function() {
    // Detect user's timezone
    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

    // Set cookie for 1 year
    document.cookie = 'user_timezone=' + encodeURIComponent(timezone) +
        '; max-age=31536000; path=/; SameSite=Strict';

    // Reload page to apply timezone
    if (!window.location.search.includes('tz_set')) {
        const separator = window.location.search ? '&' : '?';
        window.location.href = window.location.href + separator + 'tz_set=1';
    }
})();
</script>
HTML;
    }
}
