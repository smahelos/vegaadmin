<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 * Scans HTTP controllers for direct instantiation or import of concrete domain services.
 * This is a lightweight, focused variant of the broader ddd:guard command providing
 * a concise developer-facing report (historically used before integration into ddd:guard).
 *
 * It flags:
 *  - new App\Domain\<Domain>\Services\*Service(
 *  - direct constructor param type-hint of concrete *Service (when interface exists)
 *  - direct import (use) of concrete *Service (when interface exists)
 *
 * Exit codes:
 *  0 = no violations
 *  1 = violations present (or none scanned with --fail-on-empty and controllers missing)
 */
class DetectControllerServiceInstantiation extends Command
{
	/** @var string */
	protected $signature = 'code:detect-controller-service {--json : Output JSON instead of table} {--fail-on-empty : Fail if no controllers scanned}';

	/** @var string */
	protected $description = 'Detect direct concrete domain service usage inside HTTP controllers.';

	public function handle(): int
	{
		$controllersDir = app_path('Http/Controllers');
		if (!is_dir($controllersDir)) {
			$this->error('Controllers directory not found: '.$controllersDir);
			return 1;
		}

		$finder = (new Finder())
			->files()
			->in($controllersDir)
			->name('*.php');

		$serviceUseRegex = '/^use\s+(App\\\\Domain\\\\[A-Z][A-Za-z0-9_]+\\\\Services\\\\([A-Z][A-Za-z0-9_]+Service))\s*;/m';
		$newRegex         = '/new\s+App\\Domain\\[A-Z][A-Za-z0-9_]+\\Services\\[A-Z][A-Za-z0-9_]+Service\s*\(/';
		$ctorRegex        = '/__construct\([^)]*App\\Domain\\[A-Z][A-Za-z0-9_]+\\Services\\[A-Z][A-Za-z0-9_]+Service\s+\$/s';

		$rows = [];
		foreach ($finder as $file) {
			$content = $file->getContents();
			$violations = [];

			// use imports
			if (preg_match_all($serviceUseRegex, $content, $m, PREG_OFFSET_CAPTURE)) {
				foreach ($m[1] as $match) {
					[$fqcn, $offset] = $match;
					$violations[] = [
						'type' => 'import',
						'fqcn' => $fqcn,
						'line' => $this->offsetToLine($content, $offset),
					];
				}
			}
			// new expressions
			if (preg_match_all($newRegex, $content, $m2, PREG_OFFSET_CAPTURE)) {
				foreach ($m2[0] as $match) {
					[, $offset] = $match;
					$violations[] = [
						'type' => 'new',
						'fqcn' => trim($match[0]),
						'line' => $this->offsetToLine($content, $offset),
					];
				}
			}
			// constructor concrete injections
			if (preg_match_all($ctorRegex, $content, $m3, PREG_OFFSET_CAPTURE)) {
				foreach ($m3[0] as $match) {
					[, $offset] = $match;
					// Extract each service fqcn inside constructor segment
					if (preg_match_all('/App\\Domain\\[A-Z][A-Za-z0-9_]+\\Services\\[A-Z][A-Za-z0-9_]+Service/', $match[0], $inner)) {
						foreach ($inner[0] as $svcFqcn) {
							$violations[] = [
								'type' => 'ctor',
								'fqcn' => $svcFqcn,
								'line' => $this->offsetToLine($content, $offset),
							];
						}
					}
				}
			}

			if (empty($violations)) {
				continue;
			}

			$rows[] = [
				'controller' => str_replace(app_path().'/', '', $file->getRealPath()),
				'count' => count($violations),
				'details' => $violations,
			];
		}

		if ($this->option('json')) {
			$this->line(json_encode([
				'status' => empty($rows) ? 'ok' : 'violations',
				'controllers_scanned' => iterator_count($finder->getIterator()),
				'violations' => $rows,
			], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
		} else {
			if (empty($rows)) {
				$this->info('No direct concrete service usage detected in controllers.');
			} else {
				$this->error('Direct concrete service usage detected:');
				foreach ($rows as $r) {
					$this->line('- '.$r['controller'].' ('.$r['count'].' hits)');
					foreach ($r['details'] as $v) {
						$this->line('   • '.$v['type'].' @ line '.$v['line'].' -> '.$v['fqcn']);
					}
				}
			}
		}

		if (empty($rows)) {
			// Optionally fail when no controllers scanned (safety) if requested
			if ($this->option('fail-on-empty')) {
				$total = iterator_count($finder->getIterator());
				if ($total === 0) {
					return 1;
				}
			}
			return 0;
		}
		return 1;
	}

	private function offsetToLine(string $contents, int $offset): int
	{
		return substr_count(substr($contents, 0, $offset), "\n") + 1;
	}
}

