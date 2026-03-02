<?php
class Dashboard_model extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
    }

    // =========================================================================
    // Dashboard API helpers
    // =========================================================================

    /**
     * Overall catalog counts and breakdown by study type.
     */
    public function get_catalog_stats()
    {
        $rows = $this->db->query("
            SELECT
                COUNT(*)                                             AS total,
                SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END)      AS published,
                SUM(CASE WHEN published != 1 OR published IS NULL
                              THEN 1 ELSE 0 END)                     AS unpublished,
                type
            FROM surveys
            GROUP BY type
            ORDER BY total DESC
        ")->result_array();

        $total       = 0;
        $published   = 0;
        $unpublished = 0;
        $by_type     = [];

        foreach ($rows as $row) {
            $t = (int)$row['total'];
            $p = (int)$row['published'];
            $u = (int)$row['unpublished'];
            $total       += $t;
            $published   += $p;
            $unpublished += $u;
            $by_type[] = [
                'type'        => $row['type'] ?: 'unknown',
                'total'       => $t,
                'published'   => $p,
                'unpublished' => $u,
            ];
        }

        return [
            'total'       => $total,
            'published'   => $published,
            'unpublished' => $unpublished,
            'by_type'     => $by_type,
        ];
    }

    /**
     * Per-collection study counts plus pending license requests.
     * "Central catalog" is a synthetic entry covering all surveys.
     */
    public function get_collection_stats()
    {
        // 1. Global totals for the synthetic "Central catalog" entry
        $central_row = $this->db->query("
            SELECT
                COUNT(*)                                             AS total,
                SUM(CASE WHEN published = 1 THEN 1 ELSE 0 END)      AS published,
                SUM(CASE WHEN published != 1 OR published IS NULL
                              THEN 1 ELSE 0 END)                     AS unpublished
            FROM surveys
        ")->row_array();

        // 2. Per-repository counts — all repos from repositories table, even empty ones
        $repo_rows = $this->db->query("
            SELECT
                r.id                                             AS repo_id,
                r.repositoryid                                   AS repo_key,
                r.title                                          AS repo_title,
                COUNT(s.id)                                      AS total,
                SUM(CASE WHEN s.published = 1 THEN 1 ELSE 0 END) AS published,
                SUM(CASE WHEN s.published != 1 OR s.published IS NULL
                              THEN 1 ELSE 0 END)                 AS unpublished
            FROM repositories r
            LEFT JOIN surveys s ON s.repositoryid = r.repositoryid
            GROUP BY r.id, r.repositoryid, r.title
            ORDER BY total DESC, r.title ASC
        ")->result_array();

        // 3. Pending license requests per repository
        // Note: alias cannot be used in GROUP BY on SQL Server — repeat the expression.
        $pending_by_col = [];
        foreach ($this->db->query("
            SELECT
                COALESCE(NULLIF(s.repositoryid, ''), 'central') AS repo_key,
                COUNT(DISTINCT lr.id)                            AS pending
            FROM lic_requests lr
            JOIN survey_lic_requests slr ON slr.request_id = lr.id
            JOIN surveys s               ON s.id = slr.sid
            WHERE lr.status = 'PENDING'
            GROUP BY COALESCE(NULLIF(s.repositoryid, ''), 'central')
        ")->result_array() as $row) {
            $pending_by_col[$row['repo_key']] = (int)$row['pending'];
        }

        // Synthetic central entry first
        $result = [[
            'repo_key'         => 'central',
            'repo_id'          => null,
            'repo_title'       => 'Central catalog',
            'total'            => (int)$central_row['total'],
            'published'        => (int)$central_row['published'],
            'unpublished'      => (int)$central_row['unpublished'],
            'pending_requests' => isset($pending_by_col['central']) ? $pending_by_col['central'] : 0,
        ]];

        foreach ($repo_rows as $row) {
            $key      = $row['repo_key'];
            $result[] = [
                'repo_key'         => $key,
                'repo_id'          => (int)$row['repo_id'],
                'repo_title'       => $row['repo_title'],
                'total'            => (int)$row['total'],
                'published'        => (int)$row['published'],
                'unpublished'      => (int)$row['unpublished'],
                'pending_requests' => isset($pending_by_col[$key]) ? $pending_by_col[$key] : 0,
            ];
        }

        return $result;
    }

    /**
     * User summary counts + recent logins list.
     */
    public function get_user_stats_api($limit = 10)
    {
        $counts = $this->db->query("
            SELECT
                COUNT(*)                                                       AS total,
                SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END)                   AS active,
                SUM(CASE WHEN active = 0 AND (last_login IS NULL OR last_login = 0 OR last_login = created_on)
                              THEN 1 ELSE 0 END)                               AS never_logged_in,
                SUM(CASE WHEN active = 0 AND last_login IS NOT NULL AND last_login != 0 AND last_login != created_on
                              THEN 1 ELSE 0 END)                               AS disabled
            FROM users
        ")->row_array();

        $is_mysql = $this->_is_mysql();

        if ($is_mysql) {
            $recent = $this->db->query("
                SELECT id, username, email, last_login
                FROM users
                WHERE last_login > 0
                ORDER BY last_login DESC
                LIMIT " . (int)$limit . "
            ")->result_array();
        } else {
            $recent = $this->db->query("
                SELECT TOP " . (int)$limit . " id, username, email, last_login
                FROM users
                WHERE last_login > 0
                ORDER BY last_login DESC
            ")->result_array();
        }

        foreach ($recent as &$row) {
            $row['last_login_fmt'] = $row['last_login'] ? date('Y-m-d H:i', (int)$row['last_login']) : '';
        }
        unset($row);

        return [
            'total'           => (int)$counts['total'],
            'active'          => (int)$counts['active'],
            'disabled'        => (int)$counts['disabled'],
            'never_logged_in' => (int)$counts['never_logged_in'],
            'recent_logins'   => $recent,
        ];
    }

    /**
     * Last N studies ordered by changed DESC.
     */
    public function get_recent_studies($limit = 10)
    {
        $is_mysql = $this->_is_mysql();

        if ($is_mysql) {
            $rows = $this->db->query("
                SELECT s.id, s.idno, s.title, s.type, s.published, s.changed,
                       COALESCE(NULLIF(s.repositoryid, ''), 'central') AS repo_key,
                       COALESCE(r.title, 'Central catalog')             AS repo_title
                FROM surveys s
                LEFT JOIN repositories r ON r.repositoryid = s.repositoryid
                ORDER BY s.changed DESC
                LIMIT " . (int)$limit . "
            ")->result_array();
        } else {
            $rows = $this->db->query("
                SELECT TOP " . (int)$limit . " s.id, s.idno, s.title, s.type, s.published, s.changed,
                       COALESCE(NULLIF(s.repositoryid, ''), 'central') AS repo_key,
                       COALESCE(r.title, 'Central catalog')             AS repo_title
                FROM surveys s
                LEFT JOIN repositories r ON r.repositoryid = s.repositoryid
                ORDER BY s.changed DESC
            ")->result_array();
        }

        foreach ($rows as &$row) {
            $row['changed_fmt'] = $row['changed'] ? date('Y-m-d H:i', (int)$row['changed']) : '';
        }
        unset($row);

        return $rows;
    }

    /**
     * Count of PENDING license requests + top N most recent.
     */
    public function get_license_request_stats($limit = 10)
    {
        $row = $this->db->query("
            SELECT COUNT(*) AS pending FROM lic_requests WHERE status = 'PENDING'
        ")->row_array();

        $is_mysql = $this->_is_mysql();

        if ($is_mysql) {
            $top = $this->db->query("
                SELECT lr.id, lr.request_title, lr.org_rec, lr.created,
                       u.username, u.email
                FROM lic_requests lr
                LEFT JOIN users u ON u.id = lr.userid
                WHERE lr.status = 'PENDING'
                ORDER BY lr.created DESC
                LIMIT " . (int)$limit . "
            ")->result_array();
        } else {
            $top = $this->db->query("
                SELECT TOP " . (int)$limit . " lr.id, lr.request_title, lr.org_rec, lr.created,
                       u.username, u.email
                FROM lic_requests lr
                LEFT JOIN users u ON u.id = lr.userid
                WHERE lr.status = 'PENDING'
                ORDER BY lr.created DESC
            ")->result_array();
        }

        foreach ($top as &$r) {
            $r['created_fmt'] = $r['created'] ? date('Y-m-d', (int)$r['created']) : '';
        }
        unset($r);

        return [
            'pending'          => (int)$row['pending'],
            'pending_requests' => $top,
        ];
    }

    /**
     * Log table row counts with filesystem caching.
     * Reads the cache if fresh; otherwise queries the DB and refreshes the cache.
     */
    public function get_logs_health()
    {
        $cache_file        = FCPATH . 'cache/db_logs_row_counts.json';
        $cache_ttl         = $this->config->item('db_logs_row_count_cache_ttl') ?: 300;
        $warning_threshold = (int)($this->config->item('db_logs_row_count_warning') ?: 50000);

        if (file_exists($cache_file)) {
            $age = time() - filemtime($cache_file);
            if ($age < $cache_ttl) {
                $cached = json_decode(file_get_contents($cache_file), true);
                if ($cached) {
                    $cached['cached']            = true;
                    $cached['cache_age_seconds'] = $age;
                    return $cached;
                }
            }
        }

        // Cache is stale or missing — query the database directly.
        $counts    = ['sitelogs' => 0, 'api_logs' => 0];
        $is_mysql  = $this->_is_mysql();
        $is_sqlsrv = $this->_is_sqlsrv();

        try {
            if ($is_mysql) {
                // COUNT(*) for exact values; faster than information_schema estimates.
                $r = $this->db->query("SELECT COUNT(*) AS n FROM sitelogs")->row_array();
                $counts['sitelogs'] = (int)$r['n'];
                $r = $this->db->query("SELECT COUNT(*) AS n FROM api_logs")->row_array();
                $counts['api_logs'] = (int)$r['n'];
            } elseif ($is_sqlsrv) {
                // sys.partitions: metadata-only, no table scan
                $q = $this->db->query("
                    SELECT OBJECT_NAME(i.object_id) AS table_name, SUM(p.rows) AS table_rows
                    FROM sys.indexes i
                    INNER JOIN sys.partitions p
                        ON i.object_id = p.object_id AND i.index_id = p.index_id
                    WHERE i.index_id IN (0, 1)
                    AND OBJECT_NAME(i.object_id) IN ('sitelogs', 'api_logs')
                    GROUP BY i.object_id
                ");
                foreach ($q->result_array() as $row) {
                    $counts[$row['table_name']] = (int)$row['table_rows'];
                }
            }
        } catch (Exception $e) {
            return [
                'sitelogs'                   => null,
                'api_logs'                   => null,
                'warning_threshold'          => null,
                'sitelogs_exceeds_threshold' => null,
                'api_logs_exceeds_threshold' => null,
                'cached'                     => false,
                'cache_age_seconds'          => null,
            ];
        }

        $store = [
            'sitelogs'                   => $counts['sitelogs'],
            'api_logs'                   => $counts['api_logs'],
            'warning_threshold'          => $warning_threshold,
            'sitelogs_exceeds_threshold' => $counts['sitelogs'] >= $warning_threshold,
            'api_logs_exceeds_threshold' => $counts['api_logs'] >= $warning_threshold,
        ];

        // Refresh the cache so the Db_logs endpoint also benefits.
        @file_put_contents($cache_file, json_encode($store));

        return array_merge($store, [
            'cached'            => false,
            'cache_age_seconds' => 0,
        ]);
    }

    // =========================================================================
    // Internal helpers
    // =========================================================================

    private function _is_mysql()
    {
        $d = $this->db->dbdriver;
        return in_array($d, ['mysqli', 'mysql']) ||
               ($d === 'pdo' && isset($this->db->subdriver) && $this->db->subdriver === 'mysql');
    }

    private function _is_sqlsrv()
    {
        $d = $this->db->dbdriver;
        return in_array($d, ['sqlsrv', 'mssql']) ||
               ($d === 'pdo' && isset($this->db->subdriver) && $this->db->subdriver === 'sqlsrv');
    }
}
