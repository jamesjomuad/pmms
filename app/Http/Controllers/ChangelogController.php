<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class ChangelogController extends Controller
{
    /**
     * Display the changelog page.
     */
    public function index(Request $request): Response
    {
        $releases = $this->parseChangelog();

        $totalChanges = 0;
        foreach ($releases as $release) {
            foreach ($release['sections'] as $section) {
                $totalChanges += count($section['items']);
            }
        }

        return Inertia::render('Changelog', [
            'releases' => $releases,
            'stats' => [
                'total_releases' => count($releases),
                'total_changes' => $totalChanges,
                'latest_version' => $releases[0]['version'] ?? 'N/A',
            ],
        ]);
    }

    /**
     * Parse the project's CHANGELOG.md file into structured release data.
     *
     * @return array<int, array{
     *     version: string,
     *     date: string|null,
     *     is_unreleased: bool,
     *     sections: array<int, array{
     *         type: string,
     *         items: array<int, string>
     *     }>
     * }>
     */
    private function parseChangelog(): array
    {
        $changelogPath = base_path('CHANGELOG.md');

        if (! File::exists($changelogPath)) {
            return [];
        }

        $content = File::get($changelogPath);
        $lines = explode("\n", $content);

        $releases = [];
        $currentRelease = null;
        $currentSection = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Match release headers: "## [Unreleased]" or "## [0.1.0] - 2026-08-25"
            if (preg_match('/^##\s+\[?([^\]\s]+)\]?(?:\s+-\s+(\d{4}-\d{2}-\d{2}))?/', $trimmed, $matches)) {
                if ($currentRelease !== null) {
                    if ($currentSection !== null) {
                        $currentRelease['sections'][] = $currentSection;
                        $currentSection = null;
                    }
                    $releases[] = $currentRelease;
                }

                $version = $matches[1];
                $date = $matches[2] ?? null;

                $currentRelease = [
                    'version' => $version,
                    'date' => $date,
                    'is_unreleased' => strtolower($version) === 'unreleased',
                    'sections' => [],
                ];

                continue;
            }

            // Match category subheaders: "### Added", "### Fixed", etc.
            if (preg_match('/^###\s+([A-Za-z]+)/', $trimmed, $matches)) {
                if ($currentRelease !== null) {
                    if ($currentSection !== null) {
                        $currentRelease['sections'][] = $currentSection;
                    }

                    $currentSection = [
                        'type' => $matches[1],
                        'items' => [],
                    ];
                }

                continue;
            }

            // Match bullet points: "- Some change description"
            if (preg_match('/^[-*]\s+(.*)$/', $trimmed, $matches)) {
                if ($currentSection !== null) {
                    $currentSection['items'][] = $matches[1];
                }

                continue;
            }

            // Handle multi-line bullet continuation
            if ($currentSection !== null && ! empty($currentSection['items']) && ! empty($trimmed)) {
                // Ignore markdown link reference definitions like "[unreleased]: https://..."
                if (preg_match('/^\[[^\]]+\]:\s*http/', $trimmed)) {
                    continue;
                }

                // Append continuation to the last item if indented or continued text
                if (str_starts_with($line, '  ') || str_starts_with($line, "\t")) {
                    $lastIdx = count($currentSection['items']) - 1;
                    $currentSection['items'][$lastIdx] .= ' '.$trimmed;
                }
            }
        }

        if ($currentRelease !== null) {
            if ($currentSection !== null) {
                $currentRelease['sections'][] = $currentSection;
            }
            $releases[] = $currentRelease;
        }

        return $releases;
    }
}
