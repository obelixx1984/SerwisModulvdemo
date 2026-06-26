<?php
namespace App\Helpers;

class Helpers
{
    /** Bezpieczne escaping HTML */
    public static function e(mixed $v): string
    {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** Redirect po nazwie trasy */
    public static function redirect(string $route, array $params = []): never
    {
        $qs = $params ? '&' . http_build_query($params) : '';
        header('Location: ' . BASE_URL . '/index.php?route=' . $route . $qs);
        exit;
    }

    public static function redirectTo(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /** Formatowanie daty */
    public static function formatDate(?string $dt): string
    {
        if (!$dt) return '—';
        return date('d.m.Y H:i', strtotime($dt));
    }

    public static function formatDateOnly(?string $dt): string
    {
        if (!$dt) return '—';
        return date('d.m.Y', strtotime($dt));
    }

    /** Dni do terminu (+ = przyszłość, - = przeszłość) */
    public static function daysUntil(string $date): int
    {
        $now  = new \DateTime('today');
        $then = new \DateTime($date);
        $diff = (int) $now->diff($then)->days;
        return $then >= $now ? $diff : -$diff;
    }

    /**
     * POPRAWKA 1: Generuje numer zgłoszenia w formacie 0001/PREFIX/ROK
     * Używa transakcji na tabeli ticket_counters żeby uniknąć duplikatów przy równoległych zgłoszeniach
     */
    public static function generateTicketNumber(int $lineId, string $prefix): string
    {
        $db   = Database::get();
        $year = (int) date('Y');

        $db->beginTransaction();
        try {
            // Upsert — jeśli brak rekordu dla tej linii/roku, wstaw z counter=0
            $db->prepare("
                INSERT INTO ticket_counters (production_line_id, year, counter)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE counter = counter + 1
            ")->execute([$lineId, $year]);

            $row     = $db->prepare("SELECT counter FROM ticket_counters WHERE production_line_id = ? AND year = ?");
            $row->execute([$lineId, $year]);
            $counter = (int) $row->fetchColumn();

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }

        return sprintf('%04d/%s/%d', $counter, strtoupper($prefix), $year);
    }

    /**
     * POPRAWKA 9: Oblicza średni czas naprawy dla linii (od created_at do closed_at)
     * Zwraca sformatowany string lub '—'
     */
    public static function calcAvgRepairTime(array $failures): string
    {
        $closed = array_filter($failures, function ($f) {
            return !empty($f['closed_at']) && !empty($f['created_at']);
        });
        if (!$closed) return '—';

        $total = 0;
        foreach ($closed as $f) {
            $d1 = strtotime($f['created_at']);
            $d2 = strtotime($f['closed_at']);
            if ($d2 > $d1) $total += ($d2 - $d1);
        }
        $avgSec = $total / count($closed);
        $avgH   = round($avgSec / 3600, 1);
        if ($avgH < 1)   return round($avgSec / 60) . ' min';
        if ($avgH < 24)  return $avgH . 'h';
        return round($avgH / 24, 1) . ' dni';
    }

    /** Flash messages */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public static function getFlash(): ?array
    {
        $f = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $f;
    }

    /** Badge HTML dla statusu */
    public static function statusBadge(string $label, string $color): string
    {
        return '<span class="badge" style="background:' . self::e($color) . ';color:#fff;">'
            . self::e($label) . '</span>';
    }

    /** Badge HTML dla kategorii */
    public static function catBadge(string $label, string $color): string
    {
        return '<span class="badge" style="background:' . self::e($color) . ';color:#fff;">'
            . self::e($label) . '</span>';
    }

    /** Paginacja */
    public static function paginate(int $total, int $page, int $perPage): array
    {
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page       = max(1, min($page, $totalPages));
        return [
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => $totalPages,
            'offset'       => ($page - 1) * $perPage,
            'has_prev'     => $page > 1,
            'has_next'     => $page < $totalPages,
        ];
    }

    /** Typ przeglądu → etykieta PL */
    public static function reviewTypeLabel(string $type, array $customLabels = []): string
    {
        // Niestandardowa etykieta z ustawień admina
        if (!empty($customLabels[$type])) {
            return $customLabels[$type];
        }
        return match ($type) {
            'weekly'    => 'Tygodniowy',
            'monthly'   => 'Miesięczny',
            'quarterly' => 'Kwartalny',
            'biannual'  => 'Półroczny',
            'annual'    => 'Roczny',
            'ad_hoc'    => 'Doraźny',
            'periodic'  => 'Okresowy',   // ← NOWE
            default     => ucfirst($type),
        };
    }

    /**
     * Bardzo prosty konwerter Markdown → HTML (bez bibliotek/Composera).
     * Obsługuje: nagłówki (#, ##, ###), listy (- ), pogrubienie (**tekst**),
     * kod inline (`kod`), linki [tekst](url) oraz linie poziome (---).
     * Wystarczający do renderowania CHANGELOG.md w stylu zbliżonym do GitHuba.
     */
    public static function markdownToHtml(string $md): string
    {
        $lines  = explode("\n", str_replace("\r\n", "\n", $md));
        $html   = '';
        $inList = false;

        foreach ($lines as $line) {
            $trim = trim($line);

            // Linia pozioma: ---, ------, ***
            if (preg_match('/^-{3,}$/', $trim) || preg_match('/^\*{3,}$/', $trim)) {
                if ($inList) { $html .= "</ul>\n"; $inList = false; }
                $html .= "<hr>\n";
                continue;
            }

            // Nagłówki: #, ##, ### ...
            if (preg_match('/^(#{1,6})\s+(.*)$/', $trim, $m)) {
                if ($inList) { $html .= "</ul>\n"; $inList = false; }
                $level = strlen($m[1]);
                $html .= "<h{$level} class=\"md-h{$level}\">" . self::mdInline($m[2]) . "</h{$level}>\n";
                continue;
            }

            // Element listy: "- " lub "* "
            if (preg_match('/^[-*]\s+(.*)$/', $trim, $m)) {
                if (!$inList) { $html .= "<ul>\n"; $inList = true; }
                $html .= '<li>' . self::mdInline($m[1]) . "</li>\n";
                continue;
            }

            // Pusta linia — zamyka listę / robi odstęp
            if ($trim === '') {
                if ($inList) { $html .= "</ul>\n"; $inList = false; }
                continue;
            }

            // Zwykły akapit
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            $html .= '<p>' . self::mdInline($trim) . "</p>\n";
        }

        if ($inList) {
            $html .= "</ul>\n";
        }

        return $html;
    }

    /** Formatowanie inline dla markdownToHtml(): pogrubienie, kod, linki */
    private static function mdInline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $text);
        return $text;
    }
}
