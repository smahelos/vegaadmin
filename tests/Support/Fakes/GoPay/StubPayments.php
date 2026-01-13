<?php

namespace Tests\Support\Fakes\GoPay;

/**
 * Simple in-memory stub simulating GoPay payment API for behavior tests.
 */
class StubPayments
{
	public bool $failCreate = false;
	public bool $failRefund = false;
	public bool $failCancel = false;

	/** @var array<string,array{status:string,amount:float,refunded:float}> */
	private array $store = [];

	public function create(float $amount, string $currency = 'EUR'): array
	{
		if ($this->failCreate) {
			return ['success' => false, 'error' => 'create_failed'];
		}
		$id = 'GP-' . bin2hex(random_bytes(4));
		$this->store[$id] = [
			'status' => 'pending',
			'amount' => $amount,
			'refunded' => 0.0,
		];
		return [
			'success' => true,
			'payment_id' => $id,
			'payment_url' => 'https://stub-gopay.local/pay/' . $id,
			'status' => 'pending',
		];
	}

	public function status(string $id): array
	{
		if (!isset($this->store[$id])) {
			return ['success' => false, 'error' => 'not_found'];
		}
		$row = $this->store[$id];
		return [
			'success' => true,
			'payment_id' => $id,
			'status' => $row['status'],
			'amount' => $row['amount'],
			'refunded_amount' => $row['refunded'],
		];
	}

	public function cancel(string $id): bool
	{
		if ($this->failCancel || !isset($this->store[$id])) {
			return false;
		}
		$this->store[$id]['status'] = 'cancelled';
		return true;
	}

	public function refund(string $id, ?float $amount = null): array
	{
		if ($this->failRefund || !isset($this->store[$id])) {
			return ['success' => false, 'error' => 'refund_failed'];
		}
		$row = &$this->store[$id];
		$remaining = $row['amount'] - $row['refunded'];
		if ($amount === null) {
			$amount = $remaining;
		}
		if ($amount <= 0 || $amount > $remaining) {
			return ['success' => false, 'error' => 'invalid_amount'];
		}
		$row['refunded'] += $amount;
		$row['status'] = $row['refunded'] >= $row['amount'] ? 'refunded' : 'refunded'; // Simplified mapping
		return ['success' => true, 'amount' => $amount, 'status' => $row['status']];
	}
}
