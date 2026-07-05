<?php
/**
 * BizTax_QBID
 * Calculates Qualified Business Income Deduction.
 * Supports separate taxpayer + spouse calculations with per-source SSTB flags.
 *
 * File: includes/class-qbid.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class BizTax_QBID {

    /**
     * Calculate combined QBID for one person (taxpayer OR spouse).
     *
     * @param array $income {
     *   se_qbi            float  Net SE QBI (after health ins deduction and SE deduction)
     *   scorp_qbi         float  Net S-Corp QBI (after health ins deduction)
     *   se_wages          float  SE employee wages for 50% wage limit
     *   scorp_wages       float  S-Corp owner + employee wages for 50% wage limit
     *   se_sstb           bool   Is SE business an SSTB?
     *   scorp_sstb        bool   Is S-Corp business an SSTB?
     * }
     * @param float  $taxable_before_qbid  Household taxable income before any QBID
     * @param string $filing_status
     * @param int    $year
     * @return array { se_qbid, scorp_qbid, person_total }
     */
    public function calculate_person( array $income, float $taxable_before_qbid, string $filing_status, int $year ): array {
        $thresholds = $this->get_phase_out_thresholds( $year, $filing_status );

        $se_qbid    = $this->calculate_source( $income['se_qbi'],    $income['se_wages'],    $taxable_before_qbid, $thresholds, $income['se_sstb'],    $year );
        $scorp_qbid = $this->calculate_source( $income['scorp_qbi'], $income['scorp_wages'], $taxable_before_qbid, $thresholds, $income['scorp_sstb'], $year );

        return [
            'se_qbid'      => $se_qbid,
            'scorp_qbid'   => $scorp_qbid,
            'person_total' => $se_qbid + $scorp_qbid,
        ];
    }

    /**
     * Apply the overall 20%-of-taxable-income cap to the combined QBID
     * from all sources (taxpayer + spouse combined before this cap).
     */
    public function apply_overall_cap( float $combined_uncapped, float $taxable_before_qbid ): float {
        return min( $combined_uncapped, $taxable_before_qbid * 0.20 );
    }

    // ── Per-source calculation ─────────────────────────────────────────────────

    private function calculate_source( float $qbi, float $wages, float $taxable_before_qbid, array $thresholds, bool $is_sstb, int $year ): float {
        $qbi = max( 0.0, $qbi );
        if ( $qbi <= 0 || $taxable_before_qbid <= 0 ) return 0.0;

        $qbi_rate   = $qbi * 0.20;
        $wage_limit = max( 0.0, $wages ) * 0.50;
        $excess     = $taxable_before_qbid - $thresholds['start'];

        // Below phase-out threshold
        if ( $excess <= 0 ) {
            $qbid = $qbi_rate;
            // 2026 minimum rule: if QBI >= $1,000, floor is $400
            if ( $year === 2026 && $qbi >= 1000 ) {
                $qbid = max( $qbid, 400.0 );
            }
            return $qbid;
        }

        $phase_out_pct = min( 1.0, $excess / $thresholds['range'] );

        // SSTB fully phased out
        if ( $is_sstb && $phase_out_pct >= 1.0 ) return 0.0;

        // Apply SSTB phase-out reduction to QBI and wage limit
        if ( $is_sstb ) {
            $qbi        = $qbi * ( 1 - $phase_out_pct );
            $qbi_rate   = $qbi * 0.20;
            $wage_limit = $wage_limit * ( 1 - $phase_out_pct );
        }

        // Wage limit already covers QBID — no reduction needed
        if ( $qbi_rate <= $wage_limit ) return $qbi_rate;

        // Partially phase in the wage limit
        $excess_over_wage = $qbi_rate - $wage_limit;
        return $qbi_rate - ( $excess_over_wage * ( 1 - $phase_out_pct ) );
    }

    // ── Phase-out thresholds ──────────────────────────────────────────────────

    private function get_phase_out_thresholds( int $year, string $filing_status ): array {
        $data = [
            2024 => [
                'married'            => [ 'start' => 383900,  'range' => 100000 ],
                'single'             => [ 'start' => 191950,  'range' =>  50000 ],
                'married_separately' => [ 'start' => 191950,  'range' =>  50000 ],
                'head_of_household'  => [ 'start' => 191950,  'range' =>  50000 ],
            ],
            2025 => [
                'married'            => [ 'start' => 394600,  'range' => 100000 ],
                'single'             => [ 'start' => 197300,  'range' =>  50000 ],
                'married_separately' => [ 'start' => 197300,  'range' =>  50000 ],
                'head_of_household'  => [ 'start' => 197300,  'range' =>  50000 ],
            ],
            2026 => [
                'married'            => [ 'start' => 403500,  'range' => 150000 ],
                'married_separately' => [ 'start' => 201775,  'range' =>  75000 ],
                'single'             => [ 'start' => 201750,  'range' =>  75000 ],
                'head_of_household'  => [ 'start' => 201750,  'range' =>  75000 ],
            ],
        ];
        return $data[ $year ][ $filing_status ] ?? $data[2025]['single'];
    }
}
