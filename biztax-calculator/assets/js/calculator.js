/**
 * BizTax Quarterly Tax Calculator — Front-end JS v2.0
 * File: assets/js/calculator.js
 */

(function () {
  'use strict';

  // ── State ──────────────────────────────────────────────────────────────────
  const state = {
    tpType: null,   // 'se' | 'scorp' | 'both'
    spType: null,
    spouseActive: false,
    filing: '',
  };

  // ── DOM shortcuts ──────────────────────────────────────────────────────────
  const $  = id => document.getElementById(id);
  const $$ = sel => document.querySelectorAll(sel);

  // ── Formatting ─────────────────────────────────────────────────────────────
  function fmt(n) {
    if (n === null || n === undefined) return '—';
    const v = parseFloat(n);
    if (isNaN(v)) return '—';
    const abs = Math.abs(v);
    const str = abs.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    return (v < 0 ? '-$' : '$') + str;
  }
  function fmtPct(n) {
    if (n === null || n === undefined) return '—';
    return (parseFloat(n) * 100).toFixed(1) + '%';
  }

  // ── Filing status change ───────────────────────────────────────────────────
  function onFilingChange() {
    const filing = $('btc-filing').value;
    state.filing = filing;
    const isMFJ = filing === 'married';

    // Show/hide spouse toggle and spouse wages
    $('btc-spouse-toggle-wrap').style.display = isMFJ ? '' : 'none';
    $('btc-spouse-wages-wrap').style.display  = isMFJ ? '' : 'none';

    if (!isMFJ) {
      $('btc-spouse-toggle').checked = false;
      state.spouseActive = false;
      applySpouseVisibility();
    }

    // Update tp-person-label text
    $('btc-tp-person-label').textContent = 'Taxpayer';
  }

  // ── Spouse toggle ──────────────────────────────────────────────────────────
  function onSpouseToggle() {
    state.spouseActive = $('btc-spouse-toggle').checked;
    applySpouseVisibility();
  }

  function applySpouseVisibility() {
    const cols = $('btc-business-cols');
    const spouseCol = $('btc-spouse-col');

    if (state.spouseActive) {
      cols.className = 'btc-business-side-by-side';
      spouseCol.style.display = '';
    } else {
      cols.className = 'btc-business-single';
      spouseCol.style.display = 'none';
    }
  }

  // ── Income type buttons ────────────────────────────────────────────────────
  function onTypeBtn(btn) {
    const person = btn.dataset.person; // 'tp' or 'sp'
    const type   = btn.dataset.type;   // 'se', 'scorp', 'both'

    // Update button states for this person
    document.querySelectorAll(`.btc-type-btn[data-person="${person}"]`).forEach(b => {
      b.classList.toggle('active', b === btn);
    });

    if (person === 'tp') state.tpType = type;
    else                 state.spType = type;

    applyIncomeVisibility(person, type);
  }

  function applyIncomeVisibility(person, type) {
    const seBlock    = $(`btc-${person}-se`);
    const scorpBlock = $(`btc-${person}-scorp`);

    seBlock.style.display    = (type === 'se'    || type === 'both') ? '' : 'none';
    scorpBlock.style.display = (type === 'scorp' || type === 'both') ? '' : 'none';
  }

  // ── Annual option (manual vs annualize) ───────────────────────────────────
  function onAnnualOption(person, incomeType) {
    const selectId = `btc-${person}-${incomeType}-annual-option`;
    const groupId  = `btc-${person}-${incomeType}-manual-group`;
    const isManual = $(selectId).value === 'manual';
    $(groupId).style.display = isManual ? '' : 'none';
  }

  // ── SSTB buttons ───────────────────────────────────────────────────────────
  function onSstbBtn(btn) {
    const targetId = btn.dataset.target;
    const val      = btn.dataset.val;
    const siblings = btn.parentElement.querySelectorAll('.btc-sstb-btn');
    siblings.forEach(b => b.classList.toggle('active', b === btn));
    $(targetId).value = val;
  }

  // ── Deduction type ─────────────────────────────────────────────────────────
  function onDedType() {
    const isItemized = $('btc-ded-type').value === 'itemized';
    $('btc-itemized-wrap').style.display = isItemized ? '' : 'none';
  }

  // ── Credits type ───────────────────────────────────────────────────────────
  function onCreditsType() {
    const v = $('btc-credits-type').value;
    $('btc-refundable-wrap').style.display    = (v === 'refundable' || v === 'both') ? '' : 'none';
    $('btc-nonrefundable-wrap').style.display = (v === 'non_refundable' || v === 'both') ? '' : 'none';
  }

  // ── Validation ─────────────────────────────────────────────────────────────
  function validate() {
    const errors = [];
    if (!$('btc-filing').value)  errors.push('Please select a filing status.');
    if (!$('btc-quarter').value) errors.push('Please select a quarter.');
    if ($('btc-ded-type').value === 'itemized' && !$('btc-itemized').value) {
      errors.push('Please enter itemized deduction amount.');
    }
    return errors;
  }

  // ── Collect inputs ─────────────────────────────────────────────────────────
  function collectInputs() {
    const v  = id => $(id) ? $(id).value : '';
    const fv = id => parseFloat(v(id)) || 0;

    return {
      year:         v('btc-year'),
      filingStatus: v('btc-filing'),
      quarter:      v('btc-quarter'),

      priorYearAGI:    fv('btc-py-agi'),
      priorYearFedTax: fv('btc-py-tax'),

      fedTaxesPaid: fv('btc-fed-paid'),

      // Taxpayer business
      tpHasSE:    state.tpType === 'se'    || state.tpType === 'both' ? 'yes' : 'no',
      tpHasScorp: state.tpType === 'scorp' || state.tpType === 'both' ? 'yes' : 'no',

      tpSeIncome:          fv('btc-tp-se-income'),
      tpManualSeIncome:    fv('btc-tp-se-manual'),
      tpAnnualOption:      v('btc-tp-se-annual-option'),
      tpSeEmployeeSalary:  fv('btc-tp-se-emp-salary'),
      tpSeHealthInsurance: fv('btc-tp-se-health'),
      tpSeSstb:            v('btc-tp-se-sstb'),

      tpScorpIncome:          fv('btc-tp-scorp-income'),
      tpManualScorpIncome:    fv('btc-tp-scorp-manual'),
      tpScorpOwnerSalary:     fv('btc-tp-scorp-owner-salary'),
      tpScorpOwnerPretax:     fv('btc-tp-scorp-owner-pretax'),
      tpScorpEmployeeSalary:  fv('btc-tp-scorp-emp-salary'),
      tpScorpHealthInsurance: fv('btc-tp-scorp-health'),
      tpScorpSstb:            v('btc-tp-scorp-sstb'),
      tpW2Earnings:           fv('btc-tp-w2'),

      // Spouse business
      spHasSE:    state.spouseActive && (state.spType === 'se'    || state.spType === 'both') ? 'yes' : 'no',
      spHasScorp: state.spouseActive && (state.spType === 'scorp' || state.spType === 'both') ? 'yes' : 'no',

      spSeIncome:          fv('btc-sp-se-income'),
      spManualSeIncome:    fv('btc-sp-se-manual'),
      spAnnualOption:      v('btc-sp-se-annual-option'),
      spSeEmployeeSalary:  fv('btc-sp-se-emp-salary'),
      spSeHealthInsurance: fv('btc-sp-se-health'),
      spSeSstb:            v('btc-sp-se-sstb'),

      spScorpIncome:          fv('btc-sp-scorp-income'),
      spManualScorpIncome:    fv('btc-sp-scorp-manual'),
      spScorpOwnerSalary:     fv('btc-sp-scorp-owner-salary'),
      spScorpOwnerPretax:     fv('btc-sp-scorp-owner-pretax'),
      spScorpEmployeeSalary:  fv('btc-sp-scorp-emp-salary'),
      spScorpHealthInsurance: fv('btc-sp-scorp-health'),
      spScorpSstb:            v('btc-sp-scorp-sstb'),

      // Other income
      spouseWages:      fv('btc-spouse-wages'),
      interestDividends:fv('btc-interest-divs'),
      shortTermGains:   fv('btc-st-gains'),
      longTermGains:    fv('btc-lt-gains'),
      netRental:        fv('btc-rental'),
      retirement:       fv('btc-retirement'),
      otherIncome:      fv('btc-other-income'),

      // Deductions
      deductionType:      v('btc-ded-type'),
      itemizedAmount:     fv('btc-itemized'),
      sepIra401k:         fv('btc-sep-ira'),
      traditionalIra:     fv('btc-trad-ira'),
      otherDeductionsAgi: fv('btc-other-ded-agi'),

      // Credits
      creditsType:          v('btc-credits-type'),
      refundableCredits:    fv('btc-refundable'),
      nonrefundableCredits: fv('btc-nonrefundable'),
    };
  }

  // ── Populate results ────────────────────────────────────────────────────────
  function populateResults(d) {
    const set = (id, val) => { const el = $(id); if (el) el.textContent = val; };

    // Payment cards
    if (d.safe_harbor_available && d.sh_due_after_payments !== null) {
      $('btc-card-sh').textContent = fmt(d.sh_due_after_payments);
      $('btc-card-sh-sub').textContent = 'Prior year tax × multiplier';
    } else {
      $('btc-card-sh').textContent = '—';
      $('btc-card-sh-sub').textContent = 'Enter prior year data for this';
    }
    $('btc-card-cy90').textContent    = fmt(d.cy_90_due_after_payments);
    $('btc-card-cyfull').textContent  = fmt(d.cy_full_due_after_payments);

    const qLabels = { q1: 'Q1 through 3/31', q2: 'Q2 through 5/31', q3: 'Q3 through 8/31', q4: 'Full year' };
    const qLabel  = qLabels[$('btc-quarter').value] || '';
    $('btc-card-cy90-sub').textContent   = qLabel;
    $('btc-card-cyfull-sub').textContent = qLabel;

    // Income breakdown
    set('r-total-income', fmt(d.total_income));
    set('r-tp-se',        fmt(d.tp_annual_se));
    set('r-tp-scorp',     fmt(d.tp_annual_scorp));
    set('r-tp-owner',     fmt(d.tp_owner_salary_taxable));
    set('r-sp-se',        fmt(d.sp_annual_se));
    set('r-sp-scorp',     fmt(d.sp_annual_scorp));
    set('r-sp-owner',     fmt(d.sp_owner_salary_taxable));
    set('r-tp-w2',        fmt(d.tp_w2_earnings));
    set('r-spouse-wages', fmt(d.spouse_wages));
    set('r-other',        fmt(
      (d.interest_dividends||0) + (d.net_cap_gains||0) + (d.net_rental||0) + (d.retirement||0) + (d.other_income||0)
    ));

    // Show/hide spouse rows
    const showSpouse = state.spouseActive;
    ['r-sp-se-row','r-sp-scorp-row','r-sp-owner-row','r-spouse-wages-row'].forEach(id => {
      const el = $(id); if (el) el.style.display = showSpouse ? '' : 'none';
    });

    // AGI block
    set('r-agi',      fmt(d.agi));
    set('r-se-ded',   fmt(d.total_se_deduction));
    set('r-other-ded',fmt(d.total_other_deductions));

    // Taxable income
    set('r-taxable',    fmt(d.taxable_income));
    $('r-ded-label').textContent = $('btc-ded-type').value === 'itemized' ? 'Itemized deduction' : 'Standard deduction';
    set('r-deduction',  fmt(d.standard_or_itemized));
    set('r-qbid',       fmt(d.total_qbid));
    set('r-qbid-tp-se',    fmt(d.tp_qbid_se));
    set('r-qbid-tp-scorp', fmt(d.tp_qbid_scorp));
    set('r-qbid-sp-se',    fmt(d.sp_qbid_se));
    set('r-qbid-sp-scorp', fmt(d.sp_qbid_scorp));

    // QBID detail rows
    ['r-qbid-tp-se-row','r-qbid-tp-scorp-row'].forEach(id => {
      const el = $(id); if (el) el.style.display = '';
    });
    ['r-qbid-sp-se-row','r-qbid-sp-scorp-row'].forEach(id => {
      const el = $(id); if (el) el.style.display = showSpouse ? '' : 'none';
    });

    // Tax liabilities
    set('r-total-liability', fmt(d.total_liability));
    set('r-fed-income-tax',  fmt(d.fed_income_tax));
    set('r-tp-se-tax',       fmt(d.tp_se_tax));
    set('r-sp-se-tax',       fmt(d.sp_se_tax));
    set('r-add-medicare',    fmt(d.additional_medicare));
    set('r-niit',            fmt(d.niit));

    // SE tax rows
    $('r-tp-se-tax-row').style.display = (d.tp_se_tax > 0) ? '' : 'none';
    $('r-sp-se-tax-row').style.display = (d.sp_se_tax > 0 && showSpouse) ? '' : 'none';

    // Credits & net
    set('r-refundable',    fmt(d.refundable_credits));
    set('r-nonrefundable', fmt(d.nonrefundable_credits));
    set('r-net-tax-due',   fmt(d.net_tax_due));

    // Rates
    set('r-marginal',  fmtPct(d.marginal_rate));
    set('r-effective', fmtPct(d.effective_rate));

    // Quarter summary
    set('r-fed-paid',  fmt(d.fed_taxes_paid));
    set('r-sh-target', d.safe_harbor_available ? fmt(d.cumulative_sh_due) : '—');
    set('r-cy-target', fmt(d.cumulative_cy_due));
    $('r-sh-row').style.display = d.safe_harbor_available ? '' : 'none';
  }

  // ── Calculate ──────────────────────────────────────────────────────────────
  function calculate() {
    const errEl = $('btc-error-msg');
    errEl.style.display = 'none';

    const errors = validate();
    if (errors.length) {
      errEl.textContent = errors.join(' ');
      errEl.style.display = '';
      return;
    }

    const btn = $('btc-calc-btn');
    btn.disabled = true;
    btn.textContent = 'Calculating…';

    const inputs = collectInputs();
    const body   = new URLSearchParams({ action: 'biztax_calc', nonce: biztaxCalc.nonce });
    Object.entries(inputs).forEach(([k, v]) => body.append(k, v));

    fetch(biztaxCalc.ajaxUrl, { method: 'POST', body })
      .then(r => r.json())
      .then(json => {
        if (json.success) {
          populateResults(json.data);
          const results = $('btc-results');
          results.style.display = '';
          results.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
          errEl.textContent = json.data || 'Calculation failed. Please check your inputs.';
          errEl.style.display = '';
        }
      })
      .catch(() => {
        errEl.textContent = 'Network error. Please try again.';
        errEl.style.display = '';
      })
      .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Calculate quarterly estimate';
      });
  }

  // ── Init ───────────────────────────────────────────────────────────────────
  function init() {
    // Filing status
    $('btc-filing').addEventListener('change', onFilingChange);

    // Spouse toggle
    $('btc-spouse-toggle').addEventListener('change', onSpouseToggle);

    // Income type buttons
    document.querySelectorAll('.btc-type-btn').forEach(btn => {
      btn.addEventListener('click', () => onTypeBtn(btn));
    });

    // Annual option selects
    ['tp-se', 'tp-scorp', 'sp-se', 'sp-scorp'].forEach(key => {
      const [person, type] = key.split('-');
      const el = $(`btc-${person}-${type}-annual-option`);
      if (el) el.addEventListener('change', () => onAnnualOption(person, type));
    });

    // SSTB buttons
    document.querySelectorAll('.btc-sstb-btn').forEach(btn => {
      btn.addEventListener('click', () => onSstbBtn(btn));
    });

    // Deduction type
    $('btc-ded-type').addEventListener('change', onDedType);

    // Credits type
    $('btc-credits-type').addEventListener('change', onCreditsType);

    // Calculate button
    $('btc-calc-btn').addEventListener('click', calculate);
  }

  document.addEventListener('DOMContentLoaded', init);

})();
