<?php
/**
 * BizTax_Calculator_Engine
 * Orchestrates all tax calculations.
 * Supports taxpayer-only and taxpayer+spouse business income.
 *
 * File: includes/class-calculator-engine.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class BizTax_Calculator_Engine {

    private BizTax_Tax_Data $tax_data;
    private BizTax_QBID     $qbid;

    public function __construct() {
        $this->tax_data = new BizTax_Tax_Data();
        $this->qbid     = new BizTax_QBID();
    }

    /**
     * Run the full calculation.
     *
     * @param array $i  Sanitised inputs from the AJAX handler
     * @return array    Results array
     */
    public function calculate( array $i ): array {

        $year          = (int) $i['year'];
        $filing_status = $i['filing_status'];
        $quarter       = $i['quarter'];

        $quarter_months = [ 'q1' => 3, 'q2' => 5, 'q3' => 8, 'q4' => 12 ];
        $months = $quarter_months[ $quarter ] ?? 12;

        // ── LTCG brackets ─────────────────────────────────────────────────────
        $ltcg_brackets = $this->ltcg_brackets( $year, $filing_status );

        // ── Annualize business income — TAXPAYER ──────────────────────────────
        $tp_annual_se    = $this->annualize( $i['tp_se_income'],    $i['tp_manual_se_income'],    $i['tp_annual_option'], $quarter, $months, $i['tp_has_se'] );
        $tp_annual_scorp = $this->annualize( $i['tp_scorp_income'], $i['tp_manual_scorp_income'], $i['tp_annual_option'], $quarter, $months, $i['tp_has_scorp'] );

        // ── Annualize business income — SPOUSE (MFJ only) ─────────────────────
        $sp_annual_se    = $this->annualize( $i['sp_se_income'],    $i['sp_manual_se_income'],    $i['sp_annual_option'], $quarter, $months, $i['sp_has_se'] );
        $sp_annual_scorp = $this->annualize( $i['sp_scorp_income'], $i['sp_manual_scorp_income'], $i['sp_annual_option'], $quarter, $months, $i['sp_has_scorp'] );

        // ── S-Corp salary resolution — TAXPAYER ───────────────────────────────
        $tp_scorp_owner_salary_gross  = (float) $i['tp_scorp_owner_salary'];
        $tp_scorp_owner_pretax        = (float) $i['tp_scorp_owner_pretax'];
        $tp_scorp_owner_salary_taxable = max( 0.0, $tp_scorp_owner_salary_gross - $tp_scorp_owner_pretax );
        $tp_scorp_wages_for_qbid       = $tp_scorp_owner_salary_gross + (float) $i['tp_scorp_employee_salary'];

        // ── S-Corp salary resolution — SPOUSE ────────────────────────────────
        $sp_scorp_owner_salary_gross  = (float) $i['sp_scorp_owner_salary'];
        $sp_scorp_owner_pretax        = (float) $i['sp_scorp_owner_pretax'];
        $sp_scorp_owner_salary_taxable = max( 0.0, $sp_scorp_owner_salary_gross - $sp_scorp_owner_pretax );
        $sp_scorp_wages_for_qbid       = $sp_scorp_owner_salary_gross + (float) $i['sp_scorp_employee_salary'];

        // ── Other income ──────────────────────────────────────────────────────
        $spouse_wages      = (float) $i['spouse_wages'];
        $interest_divs     = (float) $i['interest_dividends'];
        $st_gains          = (float) $i['short_term_gains'];
        $lt_gains          = (float) $i['long_term_gains'];
        $rental            = (float) $i['net_rental'];
        $retirement        = (float) $i['retirement'];
        $other_income      = (float) $i['other_income'];
        $tp_w2             = (float) $i['tp_w2_earnings'];

        // Capital loss cap (-$3,000)
        $net_cap_gains = $st_gains + $lt_gains;
        if ( $net_cap_gains < -3000 ) $net_cap_gains = -3000.0;

        $regular_other = $spouse_wages + $interest_divs + $st_gains + $rental + $retirement + $other_income;
        // Adjust for cap if net gains < -3000
        $original_net = $st_gains + $lt_gains;
        if ( $original_net < -3000 ) {
            $excess_loss    = $original_net - ( -3000 );
            $regular_other -= $excess_loss;
        }

        // ── Other deductions for AGI ──────────────────────────────────────────
        $tp_se_health   = (float) $i['tp_se_health_insurance'];
        $tp_scorp_health = (float) $i['tp_scorp_health_insurance'];
        $sp_se_health   = (float) $i['sp_se_health_insurance'];
        $sp_scorp_health = (float) $i['sp_scorp_health_insurance'];
        $sep_ira        = (float) $i['sep_ira_401k'];
        $trad_ira       = (float) $i['traditional_ira'];
        $other_ded_agi  = (float) $i['other_deductions_agi'];

        $total_other_deductions = $tp_se_health + $tp_scorp_health + $sp_se_health + $sp_scorp_health + $sep_ira + $trad_ira + $other_ded_agi;

        // ── Total annual income ───────────────────────────────────────────────
        $total_income = $tp_annual_se
                      + $tp_annual_scorp
                      + $tp_scorp_owner_salary_taxable
                      + $sp_annual_se
                      + $sp_annual_scorp
                      + $sp_scorp_owner_salary_taxable
                      + $tp_w2
                      + $regular_other
                      + $lt_gains;

        // ── SE Tax — TAXPAYER ─────────────────────────────────────────────────
        $ss_wage_base = $this->ss_wage_base( $year );

        [ $tp_se_tax, $tp_se_net_earnings ] = $this->calc_se_tax(
            $tp_annual_se, $tp_scorp_owner_salary_gross, $tp_w2, $ss_wage_base
        );

        // ── SE Tax — SPOUSE ───────────────────────────────────────────────────
        // Spouse's SS wage base is separate (they are separate earners)
        [ $sp_se_tax, $sp_se_net_earnings ] = $this->calc_se_tax(
            $sp_annual_se, $sp_scorp_owner_salary_gross, $spouse_wages, $ss_wage_base
        );

        $total_se_tax = $tp_se_tax + $sp_se_tax;

        // ── Additional Medicare Tax ───────────────────────────────────────────
        $add_medicare = $this->calc_additional_medicare(
            $filing_status, $year,
            $tp_w2, $tp_scorp_owner_salary_gross, $tp_se_net_earnings,
            $sp_se_net_earnings, $sp_scorp_owner_salary_gross, $spouse_wages
        );

        // ── SE deduction (1/2 SE tax) — combined ─────────────────────────────
        $total_se_deduction = $total_se_tax * 0.5;

        // ── Standard / itemized deduction ─────────────────────────────────────
        if ( $i['deduction_type'] === 'itemized' ) {
            $deduction = (float) $i['itemized_amount'];
        } else {
            $deduction = (float) $this->tax_data->get_standard_deduction( $year, $filing_status );
        }

        // ── Taxable income before QBID ────────────────────────────────────────
        $taxable_before_qbid = max( 0.0, $total_income - $total_se_deduction - $total_other_deductions - $deduction );

        // ── QBID — TAXPAYER ───────────────────────────────────────────────────
        $tp_se_qbi    = max( 0.0, ( $tp_annual_se - $tp_se_health ) - ( $tp_se_tax * 0.5 ) );
        $tp_scorp_qbi = max( 0.0, $tp_annual_scorp - $tp_scorp_health );

        $tp_qbid_result = $this->qbid->calculate_person(
            [
                'se_qbi'      => $tp_se_qbi,
                'scorp_qbi'   => $tp_scorp_qbi,
                'se_wages'    => (float) $i['tp_se_employee_salary'],
                'scorp_wages' => $tp_scorp_wages_for_qbid,
                'se_sstb'     => ( $i['tp_se_sstb'] === 'sstb' ),
                'scorp_sstb'  => ( $i['tp_scorp_sstb'] === 'sstb' ),
            ],
            $taxable_before_qbid,
            $filing_status,
            $year
        );

        // ── QBID — SPOUSE ─────────────────────────────────────────────────────
        $sp_se_qbi    = max( 0.0, ( $sp_annual_se - $sp_se_health ) - ( $sp_se_tax * 0.5 ) );
        $sp_scorp_qbi = max( 0.0, $sp_annual_scorp - $sp_scorp_health );

        $sp_qbid_result = $this->qbid->calculate_person(
            [
                'se_qbi'      => $sp_se_qbi,
                'scorp_qbi'   => $sp_scorp_qbi,
                'se_wages'    => (float) $i['sp_se_employee_salary'],
                'scorp_wages' => $sp_scorp_wages_for_qbid,
                'se_sstb'     => ( $i['sp_se_sstb'] === 'sstb' ),
                'scorp_sstb'  => ( $i['sp_scorp_sstb'] === 'sstb' ),
            ],
            $taxable_before_qbid,
            $filing_status,
            $year
        );

        $combined_uncapped = $tp_qbid_result['person_total'] + $sp_qbid_result['person_total'];
        $total_qbid        = $this->qbid->apply_overall_cap( $combined_uncapped, $taxable_before_qbid );

        // ── Federal taxable income ────────────────────────────────────────────
        $taxable_income = max( 0.0, $taxable_before_qbid - $total_qbid );

        // ── Federal income tax — ordinary ─────────────────────────────────────
        $lt_gains_positive    = max( 0.0, $lt_gains );
        $ordinary_income_base = $total_income - $total_se_deduction - $total_other_deductions - $total_qbid - $lt_gains_positive;
        $fed_income_tax       = $this->calc_income_tax( $ordinary_income_base, $filing_status, $year, $deduction );

        // ── LTCG tax ──────────────────────────────────────────────────────────
        $ltcg_tax = 0.0;
        $lt_for_tax = 0.0;
        if ( $st_gains < 0 && $lt_gains > 0 ) {
            if ( $net_cap_gains > 0 ) $lt_for_tax = $net_cap_gains;
        } elseif ( $lt_gains > 0 ) {
            $lt_for_tax = $lt_gains;
        }
        if ( $lt_for_tax > 0 ) {
            $ordinary_taxable = max( 0.0, $taxable_income - $lt_gains );
            $ltcg_tax         = $this->calc_ltcg_tax( $lt_for_tax, $ordinary_taxable, $ltcg_brackets );
            $fed_income_tax  += $ltcg_tax;
        }

        // ── NIIT ──────────────────────────────────────────────────────────────
        $niit = $this->calc_niit( $interest_divs, $st_gains, $lt_gains, $rental, $total_income, $total_se_deduction, $filing_status, $year );

        // ── Total liability ───────────────────────────────────────────────────
        $total_liability = $fed_income_tax + $total_se_tax + $add_medicare + $niit;

        // ── Credits ───────────────────────────────────────────────────────────
        $refundable    = max( 0.0, (float) $i['refundable_credits'] );
        $nonrefundable = max( 0.0, (float) $i['nonrefundable_credits'] );
        if ( empty( $i['credits_type'] ) ) { $refundable = 0.0; $nonrefundable = 0.0; }
        elseif ( $i['credits_type'] === 'refundable' ) { $nonrefundable = 0.0; }
        elseif ( $i['credits_type'] === 'non_refundable' ) { $refundable = 0.0; }

        $net_after_nr  = max( 0.0, $total_liability - $nonrefundable );
        $net_tax_due   = $net_after_nr - $refundable;
        $annual_base   = max( 0.0, $net_tax_due );

        // ── Rates ────────────────────────────────────────────────────────────
        $marginal_rate = $this->calc_marginal_rate( $total_income - $total_se_deduction - $total_qbid, $filing_status, $year, $deduction );
        $effective_rate = $taxable_income > 0 ? $net_after_nr / $taxable_income : 0.0;

        // ── AGI ───────────────────────────────────────────────────────────────
        $agi = $total_income - $total_se_deduction - $total_other_deductions;

        // ── Safe harbor ───────────────────────────────────────────────────────
        $py_agi      = (float) $i['prior_year_agi'];
        $py_fed_tax  = (float) $i['prior_year_fed_tax'];
        $safe_harbor_available = ( $py_agi > 0 && $py_fed_tax > 0 );

        $high_income_threshold = ( $filing_status === 'married_separately' ) ? 75000 : 150000;
        $sh_multiplier         = ( $py_agi > $high_income_threshold ) ? 1.10 : 1.00;
        $sh_annual             = $py_fed_tax * $sh_multiplier;

        $q_fractions = [ 'q1' => 0.25, 'q2' => 0.50, 'q3' => 0.75, 'q4' => 1.00 ];
        $q_frac      = $q_fractions[ $quarter ] ?? 1.00;
        $fed_paid    = (float) $i['fed_taxes_paid'];

        $cumulative_sh  = $sh_annual * $q_frac;
        $cumulative_cy  = $annual_base * $q_frac;

        $sh_due_after   = $safe_harbor_available ? max( 0.0, $cumulative_sh - $fed_paid ) : null;
        $cy_90_due      = max( 0.0, ( $cumulative_cy * 0.90 ) - $fed_paid );
        $cy_full_due    = max( 0.0, $cumulative_cy - $fed_paid );

        // ── Return ────────────────────────────────────────────────────────────
        return [
            // Income breakdown
            'tp_annual_se'              => round( $tp_annual_se, 2 ),
            'tp_annual_scorp'           => round( $tp_annual_scorp, 2 ),
            'tp_owner_salary_taxable'   => round( $tp_scorp_owner_salary_taxable, 2 ),
            'sp_annual_se'              => round( $sp_annual_se, 2 ),
            'sp_annual_scorp'           => round( $sp_annual_scorp, 2 ),
            'sp_owner_salary_taxable'   => round( $sp_scorp_owner_salary_taxable, 2 ),
            'tp_w2_earnings'            => round( $tp_w2, 2 ),
            'spouse_wages'              => round( $spouse_wages, 2 ),
            'interest_dividends'        => round( $interest_divs, 2 ),
            'short_term_gains'          => round( $st_gains, 2 ),
            'long_term_gains'           => round( $lt_gains, 2 ),
            'net_cap_gains'             => round( $net_cap_gains, 2 ),
            'net_rental'                => round( $rental, 2 ),
            'retirement'                => round( $retirement, 2 ),
            'other_income'              => round( $other_income, 2 ),
            'total_income'              => round( $total_income, 2 ),
            // Deductions
            'total_se_deduction'        => round( $total_se_deduction, 2 ),
            'total_other_deductions'    => round( $total_other_deductions, 2 ),
            'standard_or_itemized'      => round( $deduction, 2 ),
            'total_qbid'                => round( $total_qbid, 2 ),
            'tp_qbid_se'                => round( $tp_qbid_result['se_qbid'], 2 ),
            'tp_qbid_scorp'             => round( $tp_qbid_result['scorp_qbid'], 2 ),
            'sp_qbid_se'                => round( $sp_qbid_result['se_qbid'], 2 ),
            'sp_qbid_scorp'             => round( $sp_qbid_result['scorp_qbid'], 2 ),
            // Income totals
            'agi'                       => round( $agi, 2 ),
            'taxable_income'            => round( $taxable_income, 2 ),
            // Tax components
            'fed_income_tax'            => round( $fed_income_tax, 2 ),
            'ltcg_tax'                  => round( $ltcg_tax, 2 ),
            'tp_se_tax'                 => round( $tp_se_tax, 2 ),
            'sp_se_tax'                 => round( $sp_se_tax, 2 ),
            'total_se_tax'              => round( $total_se_tax, 2 ),
            'additional_medicare'       => round( $add_medicare, 2 ),
            'niit'                      => round( $niit, 2 ),
            'total_liability'           => round( $total_liability, 2 ),
            // Credits & net
            'refundable_credits'        => round( $refundable, 2 ),
            'nonrefundable_credits'     => round( $nonrefundable, 2 ),
            'net_after_nr_credits'      => round( $net_after_nr, 2 ),
            'net_tax_due'               => round( $net_tax_due, 2 ),
            // Rates
            'marginal_rate'             => round( $marginal_rate, 4 ),
            'effective_rate'            => round( $effective_rate, 4 ),
            // Quarter summary
            'fed_taxes_paid'            => round( $fed_paid, 2 ),
            'safe_harbor_available'     => $safe_harbor_available,
            'cumulative_sh_due'         => round( $cumulative_sh, 2 ),
            'cumulative_cy_due'         => round( $cumulative_cy, 2 ),
            'sh_due_after_payments'     => $sh_due_after !== null ? round( $sh_due_after, 2 ) : null,
            'cy_90_due_after_payments'  => round( $cy_90_due, 2 ),
            'cy_full_due_after_payments'=> round( $cy_full_due, 2 ),
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function annualize( float $ytd, float $manual, string $option, string $quarter, int $months, bool $active ): float {
        if ( ! $active ) return 0.0;
        if ( $quarter === 'q4' ) return $ytd;
        if ( $option === 'manual' && $manual > 0 ) return $manual;
        return $months > 0 ? ( $ytd / $months ) * 12 : 0.0;
    }

    private function calc_se_tax( float $annual_se, float $scorp_owner_gross, float $w2, float $ss_base ): array {
        if ( $annual_se <= 0 ) return [ 0.0, 0.0 ];
        $net_earnings   = $annual_se * 0.9235;
        $wages_used     = $w2 + $scorp_owner_gross;
        $remaining_base = max( 0.0, $ss_base - $wages_used );
        $ss_tax         = min( $net_earnings, $remaining_base ) * 0.124;
        $medicare       = $net_earnings * 0.029;
        return [ $ss_tax + $medicare, $net_earnings ];
    }

    private function calc_additional_medicare( string $filing_status, int $year, float $tp_w2, float $tp_scorp_gross, float $tp_se_net, float $sp_se_net, float $sp_scorp_gross, float $sp_wages ): float {
        $thresholds = [ 'single' => 200000, 'married' => 250000, 'married_separately' => 125000, 'head_of_household' => 200000 ];
        $threshold  = $thresholds[ $filing_status ] ?? 200000;
        $combined   = $tp_w2 + $tp_scorp_gross + $tp_se_net + $sp_se_net + $sp_scorp_gross + $sp_wages;
        return $combined > $threshold ? ( $combined - $threshold ) * 0.009 : 0.0;
    }

    private function calc_income_tax( float $income_base, string $filing_status, int $year, float $deduction ): float {
        $brackets = $this->tax_data->get_all_brackets( $year, $filing_status );
        $taxable  = max( 0.0, $income_base - $deduction );
        $tax      = 0.0;
        $prev     = 0;
        foreach ( $brackets as $b ) {
            $in_bracket = min( $taxable - $prev, $b['bracket_max'] - $b['bracket_min'] );
            if ( $in_bracket <= 0 ) break;
            $tax += $in_bracket * $b['rate'];
            $prev = $b['bracket_max'];
            if ( $taxable <= $b['bracket_max'] ) break;
        }
        return $tax;
    }

    private function calc_marginal_rate( float $income, string $filing_status, int $year, float $deduction ): float {
        $brackets = $this->tax_data->get_all_brackets( $year, $filing_status );
        $taxable  = max( 0.0, $income - $deduction );
        foreach ( $brackets as $b ) {
            if ( $taxable <= $b['bracket_max'] ) return $b['rate'];
        }
        return end( $brackets )['rate'] ?? 0.37;
    }

    private function calc_ltcg_tax( float $lt_gains, float $ordinary_taxable, array $brackets ): float {
        $tax            = 0.0;
        $remaining      = $lt_gains;
        $prev_limit     = 0;
        foreach ( $brackets as $b ) {
            if ( $ordinary_taxable >= $b['limit'] ) { $prev_limit = $b['limit']; continue; }
            $available = max( 0.0, $b['limit'] - max( $ordinary_taxable, $prev_limit ) );
            $in_bracket = min( $remaining, $available );
            if ( $in_bracket > 0 ) { $tax += $in_bracket * $b['rate']; $remaining -= $in_bracket; }
            $prev_limit = $b['limit'];
            if ( $remaining <= 0 ) break;
        }
        return $tax;
    }

    private function calc_niit( float $interest, float $st, float $lt, float $rental, float $total_income, float $se_ded, string $filing_status, int $year ): float {
        $thresholds = [ 'single' => 200000, 'married' => 250000, 'married_separately' => 125000, 'head_of_household' => 200000 ];
        $threshold  = $thresholds[ $filing_status ] ?? 200000;
        $magi       = $total_income - $se_ded;
        if ( $magi <= $threshold ) return 0.0;
        $nii    = $interest + $st + $lt + $rental;
        $excess = $magi - $threshold;
        return max( 0.0, min( $nii, $excess ) * 0.038 );
    }

    private function ltcg_brackets( int $year, string $filing_status ): array {
        $data = [
            2024 => [
                'single'             => [ ['limit'=>47025,'rate'=>0.00],['limit'=>518900,'rate'=>0.15],['limit'=>PHP_INT_MAX,'rate'=>0.20] ],
                'married'            => [ ['limit'=>94050,'rate'=>0.00],['limit'=>583750,'rate'=>0.15],['limit'=>PHP_INT_MAX,'rate'=>0.20] ],
                'married_separately' => [ ['limit'=>47025,'rate'=>0.00],['limit'=>291850,'rate'=>0.15],['limit'=>PHP_INT_MAX,'rate'=>0.20] ],
                'head_of_household'  => [ ['limit'=>63000,'rate'=>0.00],['limit'=>551350,'rate'=>0.15],['limit'=>PHP_INT_MAX,'rate'=>0.20] ],
            ],
            2025 => [
                'single'             => [ ['limit'=>48350,'rate'=>0.00],['limit'=>533400,'rate'=>0.15],['limit'=>PHP_INT_MAX,'rate'=>0.20] ],
                'married'            => [ ['limit'=>96700,'rate'=>0.00],['limit'=>600050,'rate'=>0.15],['limit'=>PHP_INT_MAX,'rate'=>0.20] ],
                'married_separately' => [ ['limit'=>48350,'rate'=>0.00],['limit'=>300000,'rate'=>0.15],['limit'=>PHP_INT_MAX,'rate'=>0.20] ],
                'head_of_household'  => [ ['limit'=>64750,'rate'=>0.00],['limit'=>566700,'rate'=>0.15],['limit'=>PHP_INT_MAX,'rate'=>0.20] ],
            ],
            2026 => [
                'single'             => [ ['limit'=>49450,'rate'=>0.00],['limit'=>545500,'rate'=>0.15],['limit'=>PHP_INT_MAX,'rate'=>0.20] ],
                'married'            => [ ['limit'=>98900,'rate'=>0.00],['limit'=>613700,'rate'=>0.15],['limit'=>PHP_INT_MAX,'rate'=>0.20] ],
                'married_separately' => [ ['limit'=>49450,'rate'=>0.00],['limit'=>306850,'rate'=>0.15],['limit'=>PHP_INT_MAX,'rate'=>0.20] ],
                'head_of_household'  => [ ['limit'=>66200,'rate'=>0.00],['limit'=>579600,'rate'=>0.15],['limit'=>PHP_INT_MAX,'rate'=>0.20] ],
            ],
        ];
        return $data[ $year ][ $filing_status ] ?? $data[2025]['single'];
    }

    private function ss_wage_base( int $year ): float {
        return match( $year ) { 2024 => 168600, 2025 => 176100, 2026 => 184500, default => 176100 };
    }
}
