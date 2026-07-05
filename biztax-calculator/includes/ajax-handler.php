<?php

/**
 * JetEngine "Call a Hook" handler.
 * JetEngine fires: do_action( 'biztax_run_calculation', $fields, $response_handler )
 */
 add_action( 'biztax_run_calculation', function( $fields, $response_handler ) {

    $p = $fields;

    $s = fn( $key, $default = '' ) =>
        isset( $p[$key] ) && $p[$key] !== '' ? sanitize_text_field( $p[$key] ) : $default;

    $f = fn( $key, $default = 0.0 ) =>
        isset( $p[$key] ) && $p[$key] !== '' ? (float) $p[$key] : $default;

    $filing = $s( 'filing_status', 'single' );
    $valid_statuses = ['single','married','married_separately','head_of_household'];
    if ( ! in_array( $filing, $valid_statuses, true ) ) $filing = 'single';

    $quarter = $s( 'quarter' );
    $valid_quarters = ['q1','q2','q3','q4'];
    if ( ! in_array( $quarter, $valid_quarters, true ) ) {
        $quarter = 'q1';
    }

    $inputs = [
        'year'                      => $s( 'year', '2025' ),
        'filing_status'             => $filing,
        'quarter'                   => $quarter,
        'prior_year_agi'            => $f( 'prior_year_agi' ),
        'prior_year_fed_tax'        => $f( 'prior_year_fed_tax' ),
        'fed_taxes_paid'            => $f( 'fed_taxes_paid' ),
        'tp_has_se'                 => in_array( $s('tp_income_type'), ['se','both'] ) ? 'yes' : 'no',
        'tp_has_scorp'              => in_array( $s('tp_income_type'), ['scorp','both'] ) ? 'yes' : 'no',
        'tp_se_income'              => $f( 'tp_se_income' ),
        'tp_manual_se_income'       => $f( 'tp_manual_se_income' ),
        'tp_annual_option'          => $s( 'tp_annual_option', 'annualize' ),
        'tp_se_employee_salary'     => $f( 'tp_se_employee_salary' ),
        'tp_se_health_insurance'    => $f( 'tp_se_health_insurance' ),
        'tp_se_sstb'                => $s( 'tp_se_sstb', 'non_sstb' ),
        'tp_scorp_income'           => $f( 'tp_scorp_income' ),
        'tp_manual_scorp_income'    => $f( 'tp_manual_scorp_income' ),
        'tp_scorp_annual_option'    => $s( 'tp_scorp_annual_option', 'annualize' ),
        'tp_scorp_owner_salary'     => $f( 'tp_scorp_owner_salary' ),
        'tp_scorp_owner_pretax'     => $f( 'tp_scorp_owner_pretax' ),
        'tp_scorp_employee_salary'  => $f( 'tp_scorp_employee_salary' ),
        'tp_scorp_health_insurance' => $f( 'tp_scorp_health_insurance' ),
        'tp_scorp_sstb'             => $s( 'tp_scorp_sstb', 'non_sstb' ),
        'tp_w2_earnings'            => $f( 'tp_w2_earnings' ),
        'sp_has_se'                 => in_array( $s('sp_income_type'), ['se','both'] ) ? 'yes' : 'no',
        'sp_has_scorp'              => in_array( $s('sp_income_type'), ['scorp','both'] ) ? 'yes' : 'no',
        'sp_se_income'              => $f( 'sp_se_income' ),
        'sp_manual_se_income'       => $f( 'sp_manual_se_income' ),
        'sp_annual_option'          => $s( 'sp_annual_option', 'annualize' ),
        'sp_se_employee_salary'     => $f( 'sp_se_employee_salary' ),
        'sp_se_health_insurance'    => $f( 'sp_se_health_insurance' ),
        'sp_se_sstb'                => $s( 'sp_se_sstb', 'non_sstb' ),
        'sp_scorp_income'           => $f( 'sp_scorp_income' ),
        'sp_manual_scorp_income'    => $f( 'sp_manual_scorp_income' ),
        'sp_scorp_annual_option'    => $s( 'sp_scorp_annual_option', 'annualize' ),
        'sp_scorp_owner_salary'     => $f( 'sp_scorp_owner_salary' ),
        'sp_scorp_owner_pretax'     => $f( 'sp_scorp_owner_pretax' ),
        'sp_scorp_employee_salary'  => $f( 'sp_scorp_employee_salary' ),
        'sp_scorp_health_insurance' => $f( 'sp_scorp_health_insurance' ),
        'sp_scorp_sstb'             => $s( 'sp_scorp_sstb', 'non_sstb' ),
        'spouse_wages'              => $f( 'spouse_wages' ),
        'interest_dividends'        => $f( 'interest_dividends' ),
        'short_term_gains'          => $f( 'short_term_gains' ),
        'long_term_gains'           => $f( 'long_term_gains' ),
        'net_rental'                => $f( 'net_rental' ),
        'retirement'                => $f( 'retirement' ),
        'other_income'              => $f( 'other_income' ),
        'deduction_type'            => $s( 'deduction_type', 'standard' ),
        'itemized_amount'           => $f( 'itemized_amount' ),
        'sep_ira_401k'              => $f( 'sep_ira_401k' ),
        'traditional_ira'           => $f( 'traditional_ira' ),
        'other_deductions_agi'      => $f( 'other_deductions_agi' ),
        'credits_type'              => $s( 'credits_type' ),
        'refundable_credits'        => $f( 'refundable_credits' ),
        'nonrefundable_credits'     => $f( 'nonrefundable_credits' ),
    ];

    try {
        $engine  = new BizTax_Calculator_Engine();
        $results = $engine->calculate( $inputs );

        // ── Method 1: response_data array (older JFB) ─────────────────────
        $response_handler->response_data['success']    = true;
        $response_handler->response_data['calc_data']  = $results;
        $response_handler->response_data['biztax']     = $results;

        // ── Method 2: set_data method (some JFB versions) ─────────────────
        if ( method_exists( $response_handler, 'set_data' ) ) {
            $response_handler->set_data( 'calc_data', $results );
            $response_handler->set_data( 'biztax',    $results );
        }

        // ── Method 3: add_response_data method ────────────────────────────
        if ( method_exists( $response_handler, 'add_response_data' ) ) {
            $response_handler->add_response_data( 'calc_data', $results );
            $response_handler->add_response_data( 'biztax',    $results );
        }

        // ── Method 4: verbose_data property ───────────────────────────────
        if ( property_exists( $response_handler, 'verbose_data' ) ) {
            $response_handler->verbose_data['calc_data'] = $results;
            $response_handler->verbose_data['biztax']    = $results;
        }

        // ── Method 5: data property ────────────────────────────────────────
        if ( property_exists( $response_handler, 'data' ) ) {
            $response_handler->data['calc_data'] = $results;
            $response_handler->data['biztax']    = $results;
        }

    } catch ( Throwable $e ) {
        $response_handler->response_data['success'] = false;
        $response_handler->response_data['error']   = $e->getMessage();

        if ( method_exists( $response_handler, 'set_data' ) ) {
            $response_handler->set_data( 'error', $e->getMessage() );
        }
    }

}, 10, 2 );
