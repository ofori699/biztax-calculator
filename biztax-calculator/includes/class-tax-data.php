<?php
/**
 * BizTax_Tax_Data
 * Pulls tax bracket data from the tax_data CPT (JetEngine).
 * Falls back to hardcoded IRS values if CPT data is missing.
 *
 * File: includes/class-tax-data.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class BizTax_Tax_Data {

    private array $cache = [ 'brackets' => [], 'deduction' => [], 'se_rate' => [] ];

    private const VALID_STATUSES  = [ 'single', 'married', 'married_separately', 'head_of_household' ];
    private const SUPPORTED_YEARS = [ 2024, 2025, 2026 ];

    // ── Public API ────────────────────────────────────────────────────────────

    public function get_all_brackets( int $year, string $filing_status ): array {
        $year          = $this->validate_year( $year );
        $filing_status = $this->validate_status( $filing_status );
        if ( ! $year || ! $filing_status ) return [];
        return $this->load_brackets( $year, $filing_status );
    }

    public function get_standard_deduction( int $year, string $filing_status ): int {
        $year          = $this->validate_year( $year );
        $filing_status = $this->validate_status( $filing_status );
        if ( ! $year || ! $filing_status ) return 0;

        if ( isset( $this->cache['deduction'][ $year ][ $filing_status ] ) ) {
            return $this->cache['deduction'][ $year ][ $filing_status ];
        }

        $deduction = $this->load_deduction_from_cpt( $year, $filing_status )
                  ?? $this->fallback_standard_deduction( $year, $filing_status );

        $this->cache['deduction'][ $year ][ $filing_status ] = $deduction;
        return $deduction;
    }

    public function get_se_tax_rate( int $year ): float {
        $year = $this->validate_year( $year );
        if ( ! $year ) return 0.153;

        if ( isset( $this->cache['se_rate'][ $year ] ) ) {
            return $this->cache['se_rate'][ $year ];
        }

        $rate = $this->load_se_rate_from_cpt( $year ) ?? 0.153;
        $this->cache['se_rate'][ $year ] = $rate;
        return $rate;
    }

    // ── CPT Loaders ───────────────────────────────────────────────────────────

    private function load_brackets( int $year, string $filing_status ): array {
        if ( isset( $this->cache['brackets'][ $year ][ $filing_status ] ) ) {
            return $this->cache['brackets'][ $year ][ $filing_status ];
        }

        $cpt_slug = post_type_exists( 'tax_data' ) ? 'tax_data' : ( post_type_exists( 'tax-data' ) ? 'tax-data' : null );

        $brackets = [];
        if ( $cpt_slug ) {
            $posts = get_posts( [
                'post_type'     => $cpt_slug,
                'post_status'   => 'publish',
                'numberposts'   => 10,
                'fields'        => 'ids',
                'no_found_rows' => true,
                'orderby'       => 'meta_value_num',
                'meta_key'      => 'bracket_min',
                'order'         => 'ASC',
                'meta_query'    => [
                    'relation' => 'AND',
                    [ 'key' => 'tax_year',      'value' => $year,          'compare' => '=', 'type' => 'NUMERIC' ],
                    [ 'key' => 'filing_status', 'value' => $filing_status, 'compare' => '=' ],
                ],
            ] );

            foreach ( $posts as $id ) {
                $min  = (int)   get_post_meta( $id, 'bracket_min', true );
                $max  = (int)   get_post_meta( $id, 'bracket_max', true );
                $rate = (float) get_post_meta( $id, 'tax_rate',    true );
                if ( $rate <= 0 || $max < $min ) continue;
                $brackets[] = [ 'rate' => $rate, 'bracket_min' => $min, 'bracket_max' => $max, 'source' => 'cpt' ];

                if ( ! isset( $this->cache['deduction'][ $year ][ $filing_status ] ) ) {
                    $std = (int) get_post_meta( $id, 'standard_deduction', true );
                    if ( $std > 0 ) $this->cache['deduction'][ $year ][ $filing_status ] = $std;
                }
                if ( ! isset( $this->cache['se_rate'][ $year ] ) ) {
                    $se = (float) get_post_meta( $id, 'self_employment_rate', true );
                    if ( $se > 0 ) $this->cache['se_rate'][ $year ] = $se;
                }
            }
        }

        if ( empty( $brackets ) ) {
            $brackets = array_map(
                fn( $b ) => array_merge( $b, [ 'source' => 'fallback' ] ),
                $this->fallback_brackets( $year, $filing_status )
            );
        }

        $this->cache['brackets'][ $year ][ $filing_status ] = $brackets;
        return $brackets;
    }

    private function load_deduction_from_cpt( int $year, string $filing_status ): ?int {
        $cpt_slug = post_type_exists( 'tax_data' ) ? 'tax_data' : ( post_type_exists( 'tax-data' ) ? 'tax-data' : null );
        if ( ! $cpt_slug ) return null;

        $posts = get_posts( [
            'post_type' => $cpt_slug, 'post_status' => 'publish', 'numberposts' => 1,
            'fields' => 'ids', 'no_found_rows' => true,
            'meta_query' => [
                'relation' => 'AND',
                [ 'key' => 'tax_year',      'value' => $year,          'compare' => '=', 'type' => 'NUMERIC' ],
                [ 'key' => 'filing_status', 'value' => $filing_status, 'compare' => '=' ],
            ],
        ] );
        if ( empty( $posts ) ) return null;
        $v = (int) get_post_meta( $posts[0], 'standard_deduction', true );
        return $v > 0 ? $v : null;
    }

    private function load_se_rate_from_cpt( int $year ): ?float {
        $cpt_slug = post_type_exists( 'tax_data' ) ? 'tax_data' : ( post_type_exists( 'tax-data' ) ? 'tax-data' : null );
        if ( ! $cpt_slug ) return null;

        $posts = get_posts( [
            'post_type' => $cpt_slug, 'post_status' => 'publish', 'numberposts' => 1,
            'fields' => 'ids', 'no_found_rows' => true,
            'meta_query' => [ [ 'key' => 'tax_year', 'value' => $year, 'compare' => '=', 'type' => 'NUMERIC' ] ],
        ] );
        if ( empty( $posts ) ) return null;
        $v = (float) get_post_meta( $posts[0], 'self_employment_rate', true );
        return $v > 0 ? $v : null;
    }

    // ── Validation ────────────────────────────────────────────────────────────

    private function validate_year( int $year ): ?int {
        return in_array( $year, self::SUPPORTED_YEARS, true ) ? $year : null;
    }

    private function validate_status( string $s ): ?string {
        $s = strtolower( trim( $s ) );
        return in_array( $s, self::VALID_STATUSES, true ) ? $s : null;
    }

    // ── Hardcoded fallbacks (IRS-verified) ────────────────────────────────────

    private function fallback_brackets( int $year, string $filing_status ): array {
        $data = [
            2024 => [
                'single'             => [ [0.10,0,11600],[0.12,11601,47150],[0.22,47151,100525],[0.24,100526,191950],[0.32,191951,243725],[0.35,243726,609350],[0.37,609351,999999999] ],
                'married'            => [ [0.10,0,23200],[0.12,23201,94300],[0.22,94301,201050],[0.24,201051,383900],[0.32,383901,487450],[0.35,487451,731200],[0.37,731201,999999999] ],
                'married_separately' => [ [0.10,0,11600],[0.12,11601,47150],[0.22,47151,100525],[0.24,100526,191950],[0.32,191951,243725],[0.35,243726,365600],[0.37,365601,999999999] ],
                'head_of_household'  => [ [0.10,0,16550],[0.12,16551,63100],[0.22,63101,100500],[0.24,100501,191950],[0.32,191951,243700],[0.35,243701,609350],[0.37,609351,999999999] ],
            ],
            2025 => [
                'single'             => [ [0.10,0,11925],[0.12,11926,48475],[0.22,48476,103350],[0.24,103351,197300],[0.32,197301,250525],[0.35,250526,626350],[0.37,626351,999999999] ],
                'married'            => [ [0.10,0,23850],[0.12,23851,96950],[0.22,96951,206700],[0.24,206701,394600],[0.32,394601,501050],[0.35,501051,751600],[0.37,751601,999999999] ],
                'married_separately' => [ [0.10,0,11925],[0.12,11926,48475],[0.22,48476,103350],[0.24,103351,197300],[0.32,197301,250525],[0.35,250526,375800],[0.37,375801,999999999] ],
                'head_of_household'  => [ [0.10,0,17000],[0.12,17001,64850],[0.22,64851,103350],[0.24,103351,197300],[0.32,197301,250500],[0.35,250501,626350],[0.37,626351,999999999] ],
            ],
            2026 => [
                'single'             => [ [0.10,0,12400],[0.12,12401,50400],[0.22,50401,105700],[0.24,105701,201775],[0.32,201776,256225],[0.35,256226,640600],[0.37,640601,999999999] ],
                'married'            => [ [0.10,0,24800],[0.12,24801,100800],[0.22,100801,211400],[0.24,211401,403550],[0.32,403551,512450],[0.35,512451,768700],[0.37,768701,999999999] ],
                'married_separately' => [ [0.10,0,12400],[0.12,12401,50400],[0.22,50401,105700],[0.24,105701,201775],[0.32,201776,256225],[0.35,256226,384350],[0.37,384351,999999999] ],
                'head_of_household'  => [ [0.10,0,17700],[0.12,17701,67450],[0.22,67451,105700],[0.24,105701,201775],[0.32,201776,256200],[0.35,256201,640600],[0.37,640601,999999999] ],
            ],
        ];
        $raw = $data[ $year ][ $filing_status ] ?? [];
        return array_map( fn($r) => [ 'rate' => $r[0], 'bracket_min' => $r[1], 'bracket_max' => $r[2] ], $raw );
    }

    private function fallback_standard_deduction( int $year, string $filing_status ): int {
        $data = [
            2024 => [ 'single' => 14600, 'married' => 29200, 'married_separately' => 14600, 'head_of_household' => 21900 ],
            2025 => [ 'single' => 15750, 'married' => 31500, 'married_separately' => 15750, 'head_of_household' => 23625 ],
            2026 => [ 'single' => 16100, 'married' => 32200, 'married_separately' => 16100, 'head_of_household' => 24150 ],
        ];
        return $data[ $year ][ $filing_status ] ?? 0;
    }
}
