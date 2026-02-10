<?php
declare(strict_types=1);

namespace BFS\Helpers;

/**
 * Product description formatter (safe + readable).
 *
 * Rules:
 * - If content already contains HTML: keep it (sanitized).
 * - If plain text: generate semantic, breathable HTML:
 *   - Split dense text into shorter paragraphs (2 sentences/block).
 *   - If it is a single long sentence, split by strong connectors (La/En/El/Además...) and/or commas.
 *   - Convert bullets and key:value lines when present.
 *
 * Output is always sanitized with wp_kses_post().
 */
final class DescriptionFormatter {

	public static function format(string $raw): string {
		$raw = trim($raw);
		if ($raw === '') {
			return '';
		}

		// Keep existing HTML (but sanitize).
		if (self::looks_like_html($raw)) {
			return wp_kses_post($raw);
		}

		$text  = self::normalize_whitespace($raw);
		$lines = self::split_lines($text);

		// Mega-line (very common in imports): structure by sentences/clauses.
		if (count($lines) === 1 && mb_strlen($lines[0]) > 220) {
			$chunks = self::smart_blocks_from_text($lines[0]);

			$blocks = [];
			foreach ($chunks as $c) {
				$c = trim((string) $c);
				if ($c !== '') {
					$blocks[] = ['type' => 'p', 'content' => $c];
				}
			}

			return wp_kses_post(self::blocks_to_html($blocks));
		}

		// Line-based parsing (handles bullets, key:value, headings).
		$blocks = self::lines_to_blocks($lines);

		// Split overly long paragraphs into smaller ones.
		$blocks = self::split_long_blocks($blocks);

		return wp_kses_post(self::blocks_to_html($blocks));
	}

	/* ---------------------------- Detection & cleanup ---------------------------- */

	private static function looks_like_html(string $raw): bool {
		return preg_match('/<\s*([a-z][a-z0-9]*)\b[^>]*>/i', $raw) === 1;
	}

	private static function normalize_whitespace(string $text): string {
		$text = str_replace(["\r\n", "\r"], "\n", $text);
		$text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
		$text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;
		return trim($text);
	}

	/**
	 * @return string[] Lines, keeping empty lines as separators.
	 */
	private static function split_lines(string $text): array {
		$rawLines = explode("\n", $text);
		$lines    = [];

		foreach ($rawLines as $l) {
			$lines[] = trim($l); // keep '' to mark paragraph breaks
		}

		if (count(array_filter($lines, static fn($v) => $v !== '')) === 0) {
			return [trim($text)];
		}

		return $lines;
	}

	/* ---------------------------- Mega-line splitter ---------------------------- */

	/**
	 * Create breathable blocks from a dense plain-text blob.
	 *
	 * @return string[]
	 */
	private static function smart_blocks_from_text(string $text): array {
		$text = trim($text);
		if ($text === '') {
			return [];
		}

		// 1) Try sentence splitting first.
		$sentences = self::sentence_split($text);

		// If we got multiple sentences, group 2 per paragraph.
		if (count($sentences) > 1) {
			return self::group_sentences($sentences, 2);
		}

		// 2) Single long sentence: split by strong connectors with initial caps (Spanish-friendly).
		$clauses = self::split_by_connectors($text);

		// If connectors didn't help, split by commas (but keep it conservative).
		if (count($clauses) <= 1) {
			$clauses = self::split_by_commas($text);
		}

		// Finally pack into paragraphs by max length.
		return self::pack_by_length($clauses, 260);
	}

	/**
	 * @return string[]
	 */
	private static function sentence_split(string $text): array {
		$parts = preg_split('/(?<=[.!?])\s+(?=[\p{L}"“”\(\[])/u', $text) ?: [$text];
		$out = [];
		foreach ($parts as $p) {
			$p = trim((string) $p);
			if ($p !== '') {
				$out[] = $p;
			}
		}
		return $out ?: [trim($text)];
	}

	/**
	 * @param string[] $sentences
	 * @return string[]
	 */
	private static function group_sentences(array $sentences, int $perBlock): array {
		$blocks = [];
		$buf    = [];
		foreach ($sentences as $s) {
			$buf[] = $s;
			if (count($buf) >= $perBlock) {
				$blocks[] = trim(implode(' ', $buf));
				$buf = [];
			}
		}
		if (!empty($buf)) {
			$blocks[] = trim(implode(' ', $buf));
		}
		return $blocks;
	}

	/**
	 * Split by common section-starters that often indicate a new idea.
	 * Works well when the text is one long sentence but has capitalized segments.
	 *
	 * @return string[]
	 */
	private static function split_by_connectors(string $text): array {
		// Add more connectors if your catalog uses other patterns.
		$pattern = '/\s+(?=(?:La|El|En|Además|Tamb(?:ién|ien)|Por\s+ello|Por\s+eso|Su\s+|Esta\s+|Este\s+|A\s+continuaci(?:ón|on)|Por\s+su)\b)/u';
		$parts = preg_split($pattern, $text) ?: [$text];

		$out = [];
		foreach ($parts as $p) {
			$p = trim((string) $p);
			if ($p !== '') {
				$out[] = $p;
			}
		}
		return $out ?: [trim($text)];
	}

	/**
	 * @return string[]
	 */
	private static function split_by_commas(string $text): array {
		// Only split on comma+space; keeps numbers like 1,5 intact in most cases.
		$parts = preg_split('/,\s+/u', $text) ?: [$text];

		$out = [];
		foreach ($parts as $i => $p) {
			$p = trim((string) $p);
			if ($p === '') {
				continue;
			}
			// Re-add comma except first part so punctuation stays natural.
			$out[] = $i === 0 ? $p : ($p !== '' ? ($p) : '');
		}
		return $out ?: [trim($text)];
	}

	/**
	 * Pack fragments into paragraphs not exceeding max chars (roughly).
	 *
	 * @param string[] $fragments
	 * @return string[]
	 */
	private static function pack_by_length(array $fragments, int $maxChars): array {
		$blocks = [];
		$buf = '';

		foreach ($fragments as $frag) {
			$frag = trim((string) $frag);
			if ($frag === '') {
				continue;
			}

			$append = $buf === '' ? $frag : ($buf . ' ' . $frag);

			if (mb_strlen($append) > $maxChars && $buf !== '') {
				$blocks[] = trim($buf);
				$buf = $frag;
				continue;
			}

			$buf = $append;
		}

		if (trim($buf) !== '') {
			$blocks[] = trim($buf);
		}

		// Fallback
		return $blocks ?: [trim(implode(' ', $fragments))];
	}

	/* ---------------------------- Line-based parsing ---------------------------- */

	/**
	 * @return array<int, array{type:string, content:mixed}>
	 */
	private static function lines_to_blocks(array $lines): array {
		$blocks = [];
		$buffer = [];

		$flushParagraph = static function () use (&$blocks, &$buffer): void {
			$txt = trim(implode(' ', array_filter($buffer, static fn($v) => $v !== '')));
			$buffer = [];
			if ($txt !== '') {
				$blocks[] = ['type' => 'p', 'content' => $txt];
			}
		};

		foreach ($lines as $line) {
			if ($line === '') {
				$flushParagraph();
				continue;
			}

			if (preg_match('/^([•\-\*]|&bull;)\s+(.+)$/u', $line, $m)) {
				$flushParagraph();
				$blocks[] = ['type' => 'li', 'content' => trim($m[2])];
				continue;
			}

			if (preg_match('/^([^:]{2,60}):\s*(.+)$/u', $line, $m)) {
				$flushParagraph();
				$blocks[] = ['type' => 'kv', 'content' => [trim($m[1]), trim($m[2])]];
				continue;
			}

			if (self::is_heading_candidate($line)) {
				$flushParagraph();
				$blocks[] = ['type' => 'h3', 'content' => $line];
				continue;
			}

			$buffer[] = $line;
		}

		$flushParagraph();
		return $blocks;
	}

	private static function is_heading_candidate(string $line): bool {
		$len = mb_strlen($line);
		if ($len < 4 || $len > 70) {
			return false;
		}
		if (preg_match('/[.!?]$/u', $line)) {
			return false;
		}
		if (substr_count($line, ',') >= 2) {
			return false;
		}
		if (preg_match('/https?:\/\/|www\./i', $line)) {
			return false;
		}
		return preg_match('/^[\p{L}\p{N}\s\-–—()]+$/u', $line) === 1;
	}

	/**
	 * @param array<int, array{type:string, content:mixed}> $blocks
	 * @return array<int, array{type:string, content:mixed}>
	 */
	private static function split_long_blocks(array $blocks): array {
		$out = [];
		foreach ($blocks as $b) {
			if (($b['type'] ?? '') !== 'p') {
				$out[] = $b;
				continue;
			}

			$text = (string) ($b['content'] ?? '');
			if (mb_strlen($text) <= 260) {
				$out[] = $b;
				continue;
			}

			$chunks = self::smart_blocks_from_text($text);
			foreach ($chunks as $c) {
				$out[] = ['type' => 'p', 'content' => $c];
			}
		}
		return $out;
	}

	/* ---------------------------- Rendering ---------------------------- */

	/**
	 * Render blocks to minimal HTML (safe, then wp_kses_post on top-level).
	 *
	 * @param array<int, mixed> $blocks
	 */
	private static function blocks_to_html(array $blocks): string {
		$html   = '';
		$inList = false;

		foreach ($blocks as $b) {
			// Hardening: if a string sneaks in, treat as paragraph.
			if (!is_array($b)) {
				$b = ['type' => 'p', 'content' => (string) $b];
			}

			$type    = (string) ($b['type'] ?? 'p');
			$content = $b['content'] ?? '';

			if ($type === 'li') {
				if (!$inList) {
					$html .= '<ul>';
					$inList = true;
				}
				$html .= '<li>' . esc_html((string) $content) . '</li>';
				continue;
			}

			if ($inList) {
				$html .= '</ul>';
				$inList = false;
			}

			if ($type === 'h3') {
				$html .= '<h3>' . esc_html((string) $content) . '</h3>';
				continue;
			}

			if ($type === 'kv' && is_array($content) && count($content) === 2) {
				[$k, $v] = $content;
				$html .= '<p><strong>' . esc_html((string) $k) . ':</strong> ' . esc_html((string) $v) . '</p>';
				continue;
			}

			$html .= '<p>' . esc_html((string) $content) . '</p>';
		}

		if ($inList) {
			$html .= '</ul>';
		}

		return $html;
	}
}
