# BizTax Quarterly Estimated Tax Calculator

A robust WordPress plugin designed for business owners and individuals to calculate their quarterly estimated federal taxes. It supports complex tax scenarios, including self-employment income, S-Corp dividends/salary, and spouse business income for joint filers.

## Features

- **Quarterly Calculations:** Supports Q1 through Q4 tax estimations with automatic income annualization.
- **Support for Multi-Earner Households:** Handles separate business incomes for the taxpayer and spouse (MFJ).
- **Comprehensive Tax Logic:**
    - Self-Employment (SE) tax calculations.
    - Qualified Business Income Deduction (QBID) supporting SSTB and non-SSTB businesses.
    - Long-Term Capital Gains (LTCG) tax brackets.
    - Net Investment Income Tax (NIIT) and Additional Medicare Tax.
- **Safe Harbor Tracking:** Helps users determine "Safe Harbor" payment amounts based on prior-year AGI and tax liability.
- **Interactive UI:** A front-end calculator interface built with tailored CSS and JS, utilizing AJAX for real-time results without page reloads.

## Installation

1. Upload the `biztax-calculator` folder to your WordPress site's `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Use the following shortcode on any page or post to display the calculator:
   `[biztax_quarterly_calculator]`

## File Structure

- `biztax-calculator.php`: The main plugin entry point, containing metadata and shortcode registration.
- `assets/`: Contains front-end styling (`css/calculator.css`) and logic (`js/calculator.js`).
- `includes/`:
    - `class-calculator-engine.php`: The core logic engine that orchestrates all tax calculations.
    - `class-tax-data.php`: Contains tax brackets, standard deductions, and wage bases for multiple years (2024-2026).
    - `class-qbid.php`: Handles the complex logic for the Section 199A QBID.
    - `ajax-handler.php`: Manages the communication between the front-end form and the back-end calculator engine.
    - `template-calculator.php`: The HTML template for the calculator's front-end interface.

## Technical Details

- **Version:** 2.0.0
- **Author:** BizTax Playbook
- **Minimum Requirements:** PHP 7.4+ (uses typed properties), WordPress 5.0+
- **Security:** Implements WordPress Nonces for AJAX security and strict data sanitization.

## Support & Updates

This plugin includes tax data for the years 2024, 2025, and 2026. Formulas and thresholds are updated based on the latest IRS inflation adjustments.
