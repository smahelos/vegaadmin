<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Payment\Contracts\GatewayRegistryInterface;
use App\Domain\Payment\Contracts\PaymentGatewayInterface;

/**
 * Validates that payment gateway adapters are properly registered in the GatewayRegistry
 * and implement required contract methods. Produces a concise report for CI or manual audit.
 */
class PaymentAdapterGuardCommand extends Command
{
	/** @var string */
	protected $signature = 'payment:adapter-guard {--json : Output JSON report}';
	/** @var string */
	protected $description = 'Guard ensuring payment gateway adapters are registered and valid.';

	public function handle(GatewayRegistryInterface $registry): int
	{
		// Force resolution of registry to ensure provider executed.
		$gatewayNames = $registry->names();
		sort($gatewayNames);

		$violations = [];
		$report = [];
		foreach ($gatewayNames as $name) {
			$gateway = $registry->get($name);
			if (!$gateway) {
				$violations[] = [
					'type' => 'missing_instance',
					'gateway' => $name,
					'message' => 'Registered name returns null instance',
				];
				continue;
			}
			$class = get_class($gateway);
			$missingMethods = $this->missingMethods($gateway, [
				'createPayment', 'processPaymentReturn', 'verifyPayment', 'refundPayment', 'cancelPayment'
			]);
			if (!empty($missingMethods)) {
				$violations[] = [
					'type' => 'missing_methods',
					'gateway' => $name,
					'class' => $class,
					'missing' => $missingMethods,
					'message' => 'Gateway missing required methods',
				];
			}
			if (!$gateway instanceof PaymentGatewayInterface) {
				$violations[] = [
					'type' => 'not_implementing_contract',
					'gateway' => $name,
					'class' => $class,
					'message' => 'Gateway does not implement PaymentGatewayInterface',
				];
			}
			$report[] = [
				'name' => $name,
				'class' => $class,
				'currencies' => method_exists($gateway, 'getSupportedCurrencies') ? $gateway->getSupportedCurrencies() : [],
				'display_name' => method_exists($gateway, 'getDisplayName') ? $gateway->getDisplayName() : $name,
				'recurring_support' => method_exists($gateway, 'supportsRecurringPayments') ? (bool)$gateway->supportsRecurringPayments($name) : null,
				'status' => empty($missingMethods) && $gateway instanceof PaymentGatewayInterface ? 'ok' : 'invalid',
			];
		}

		if ($this->option('json')) {
			$this->line(json_encode([
				'gateways' => $report,
				'violations' => $violations,
			], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
		} else {
			if (empty($report)) {
				$this->warn('No gateways registered.');
			} else {
				$this->table(['Gateway','Class','Currencies','Display','Recurring','Status'], array_map(function($g){
					return [
						$g['name'],
						$g['class'],
						implode(',', $g['currencies']),
						$g['display_name'],
						$g['recurring_support'] === null ? 'n/a' : ($g['recurring_support'] ? 'yes' : 'no'),
						$g['status']
					];
				}, $report));
			}
			if (!empty($violations)) {
				$this->error(count($violations).' gateway violation(s) detected.');
			} else {
				$this->info('All registered gateways valid.');
			}
		}

		return empty($violations) ? 0 : 1;
	}

	/** @param object $gateway */
	private function missingMethods(object $gateway, array $required): array
	{
		$missing = [];
		foreach ($required as $method) {
			if (!method_exists($gateway, $method)) {
				$missing[] = $method;
			}
		}
		return $missing;
	}
}

