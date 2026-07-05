<div class="btc-wrap" id="btc-calculator">

  <div class="btc-legend">
    <span class="btc-legend-item"><span class="btc-req-dot"></span> Required</span>
    <span class="btc-legend-item"><span class="btc-opt-dot"></span> Optional</span>
  </div>

  <!-- ── SECTION 1: Tax year, filing status, quarter ─────────────────── -->
  <div class="btc-section">
    <div class="btc-section-head">
      <span class="btc-section-num">1</span>
      <div>
        <div class="btc-section-title">Tax year &amp; filing details</div>
        <div class="btc-section-sub">Required to set the correct brackets, deductions, and income fields</div>
      </div>
    </div>
    <div class="btc-grid3">
      <div class="btc-field">
        <label class="btc-label" for="btc-year">Tax year <span class="btc-req">*</span></label>
        <select id="btc-year">
          <option value="2025" selected>2025</option>
          <option value="2024">2024</option>
          <option value="2026">2026</option>
        </select>
      </div>
      <div class="btc-field">
        <label class="btc-label" for="btc-filing">Filing status <span class="btc-req">*</span></label>
        <select id="btc-filing" required>
          <option value="">Select status</option>
          <option value="single">Single</option>
          <option value="married">Married filing jointly</option>
          <option value="married_separately">Married filing separately</option>
          <option value="head_of_household">Head of household</option>
        </select>
      </div>
      <div class="btc-field">
        <label class="btc-label" for="btc-quarter">Quarter <span class="btc-req">*</span></label>
        <select id="btc-quarter" required>
          <option value="">Select quarter</option>
          <option value="q1">Q1 — due 4/15</option>
          <option value="q2">Q2 — due 6/15</option>
          <option value="q3">Q3 — due 9/15</option>
          <option value="q4">Q4 — due 1/15</option>
        </select>
      </div>
    </div>
  </div>

  <!-- ── SECTION 2: Prior year (optional) ───────────────────────────── -->
  <div class="btc-section">
    <div class="btc-section-head">
      <span class="btc-section-num">2</span>
      <div>
        <div class="btc-section-title">Prior year reference <span class="btc-opt-badge">optional</span></div>
        <div class="btc-section-sub">Used only for the safe-harbor calculation — skip if unavailable</div>
      </div>
    </div>
    <div class="btc-grid2">
      <div class="btc-field">
        <label class="btc-label" for="btc-py-agi">Prior year AGI
          <span class="btc-tooltip" data-tip="Adjusted Gross Income from prior year Form 1040">?</span>
        </label>
        <input type="number" id="btc-py-agi" placeholder="e.g. 120000" min="0" step="0.01">
      </div>
      <div class="btc-field">
        <label class="btc-label" for="btc-py-tax">Prior year federal tax
          <span class="btc-tooltip" data-tip="&quot;Total Tax&quot; line on prior year Form 1040">?</span>
        </label>
        <input type="number" id="btc-py-tax" placeholder="e.g. 22000" min="0" step="0.01">
      </div>
    </div>
  </div>

  <!-- ── SECTION 3: Business income ─────────────────────────────────── -->
  <div class="btc-section">
    <div class="btc-section-head">
      <span class="btc-section-num">3</span>
      <div>
        <div class="btc-section-title">Business income</div>
        <div class="btc-section-sub">Select income type — QBID inputs and SSTB status appear under each</div>
      </div>
    </div>

    <!-- MFJ side-by-side wrapper -->
    <div id="btc-business-cols" class="btc-business-single">

      <!-- TAXPAYER column -->
      <div class="btc-person-block btc-tp-block">
        <div class="btc-person-label btc-tp-label" id="btc-tp-person-label">Taxpayer</div>
        <div class="btc-type-btns">
          <button type="button" class="btc-type-btn" data-person="tp" data-type="se">Self-employed</button>
          <button type="button" class="btc-type-btn" data-person="tp" data-type="scorp">S-Corp</button>
          <button type="button" class="btc-type-btn" data-person="tp" data-type="both">Both</button>
        </div>

        <!-- Taxpayer SE block -->
        <div class="btc-income-block" id="btc-tp-se" style="display:none">
          <div class="btc-income-block-title">Self-employment</div>
          <div class="btc-grid2">
            <div class="btc-field">
              <label class="btc-label">SE net income YTD <span class="btc-req">*</span></label>
              <input type="number" id="btc-tp-se-income" placeholder="e.g. 60000" min="0" step="0.01">
            </div>
            <div class="btc-field">
              <label class="btc-label">Annual income option</label>
              <select id="btc-tp-se-annual-option">
                <option value="annualize">Annualize YTD</option>
                <option value="manual">Manual entry</option>
              </select>
            </div>
          </div>
          <div class="btc-field btc-manual-field" id="btc-tp-se-manual-group" style="display:none">
            <label class="btc-label">Manual annual SE income</label>
            <input type="number" id="btc-tp-se-manual" placeholder="Full year estimate" min="0" step="0.01">
          </div>
          <div class="btc-sub-block">
            <div class="btc-sub-title">QBID — SE</div>
            <div class="btc-grid2">
              <div class="btc-field">
                <label class="btc-label">Employee salaries (annual)</label>
                <input type="number" id="btc-tp-se-emp-salary" placeholder="e.g. 0" min="0" step="0.01">
              </div>
              <div class="btc-field">
                <label class="btc-label">SE health insurance deduction</label>
                <input type="number" id="btc-tp-se-health" placeholder="e.g. 0" min="0" step="0.01">
              </div>
            </div>
            <div class="btc-sub-title btc-sstb-label">SSTB status
              <span class="btc-tooltip" data-tip="Specified Service Trade or Business — health, law, accounting, consulting, financial services, etc.">?</span>
            </div>
            <div class="btc-sstb-row">
              <button type="button" class="btc-sstb-btn active" data-target="btc-tp-se-sstb" data-val="non_sstb">Non-SSTB</button>
              <button type="button" class="btc-sstb-btn" data-target="btc-tp-se-sstb" data-val="sstb">SSTB</button>
            </div>
            <input type="hidden" id="btc-tp-se-sstb" value="non_sstb">
          </div>
        </div>

        <!-- Taxpayer S-Corp block -->
        <div class="btc-income-block" id="btc-tp-scorp" style="display:none">
          <div class="btc-income-block-title">S-Corporation</div>
          <div class="btc-grid2">
            <div class="btc-field">
              <label class="btc-label">S-Corp net income YTD <span class="btc-req">*</span></label>
              <input type="number" id="btc-tp-scorp-income" placeholder="e.g. 80000" min="0" step="0.01">
            </div>
            <div class="btc-field">
              <label class="btc-label">Annual income option</label>
              <select id="btc-tp-scorp-annual-option">
                <option value="annualize">Annualize YTD</option>
                <option value="manual">Manual entry</option>
              </select>
            </div>
          </div>
          <div class="btc-field btc-manual-field" id="btc-tp-scorp-manual-group" style="display:none">
            <label class="btc-label">Manual annual S-Corp income</label>
            <input type="number" id="btc-tp-scorp-manual" placeholder="Full year estimate" min="0" step="0.01">
          </div>
          <div class="btc-sub-block">
            <div class="btc-sub-title">QBID — S-Corp</div>
            <div class="btc-grid3-tight">
              <div class="btc-field">
                <label class="btc-label">Owner gross salary
                  <span class="btc-tooltip" data-tip="Gross W-2 salary before pre-tax deductions — includes 2% S-Corp health premiums">?</span>
                </label>
                <input type="number" id="btc-tp-scorp-owner-salary" placeholder="e.g. 60000" min="0" step="0.01">
              </div>
              <div class="btc-field">
                <label class="btc-label">Owner pre-tax contributions
                  <span class="btc-tooltip" data-tip="401(k), HSA, Section 125 — do NOT include 2% health premiums">?</span>
                </label>
                <input type="number" id="btc-tp-scorp-owner-pretax" placeholder="e.g. 5000" min="0" step="0.01">
              </div>
              <div class="btc-field">
                <label class="btc-label">Employee salaries (annual)</label>
                <input type="number" id="btc-tp-scorp-emp-salary" placeholder="e.g. 0" min="0" step="0.01">
              </div>
            </div>
            <div class="btc-field" style="margin-top:10px">
              <label class="btc-label">S-Corp SE health insurance deduction
                <span class="btc-tooltip" data-tip="2% shareholder health premium included in owner W-2 wages — reduces S-Corp QBI">?</span>
              </label>
              <input type="number" id="btc-tp-scorp-health" placeholder="e.g. 0" min="0" step="0.01">
            </div>
            <div class="btc-sub-title btc-sstb-label" style="margin-top:12px">SSTB status</div>
            <div class="btc-sstb-row">
              <button type="button" class="btc-sstb-btn active" data-target="btc-tp-scorp-sstb" data-val="non_sstb">Non-SSTB</button>
              <button type="button" class="btc-sstb-btn" data-target="btc-tp-scorp-sstb" data-val="sstb">SSTB</button>
            </div>
            <input type="hidden" id="btc-tp-scorp-sstb" value="non_sstb">
          </div>
        </div>

        <!-- Taxpayer W-2 day job -->
        <div class="btc-field" style="margin-top:12px">
          <label class="btc-label" for="btc-tp-w2">Owner day job W-2 earnings
            <span class="btc-tooltip" data-tip="Wages from a separate employer — affects the Social Security wage base">?</span>
          </label>
          <input type="number" id="btc-tp-w2" placeholder="e.g. 0" min="0" step="0.01">
        </div>
      </div><!-- /taxpayer -->

      <!-- SPOUSE column (MFJ only) -->
      <div class="btc-person-block btc-sp-block" id="btc-spouse-col" style="display:none">
        <div class="btc-person-label btc-sp-label">Spouse</div>
        <div class="btc-type-btns">
          <button type="button" class="btc-type-btn" data-person="sp" data-type="se">Self-employed</button>
          <button type="button" class="btc-type-btn" data-person="sp" data-type="scorp">S-Corp</button>
          <button type="button" class="btc-type-btn" data-person="sp" data-type="both">Both</button>
        </div>

        <!-- Spouse SE block -->
        <div class="btc-income-block" id="btc-sp-se" style="display:none">
          <div class="btc-income-block-title">Self-employment</div>
          <div class="btc-grid2">
            <div class="btc-field">
              <label class="btc-label">SE net income YTD <span class="btc-req">*</span></label>
              <input type="number" id="btc-sp-se-income" placeholder="e.g. 40000" min="0" step="0.01">
            </div>
            <div class="btc-field">
              <label class="btc-label">Annual income option</label>
              <select id="btc-sp-se-annual-option">
                <option value="annualize">Annualize YTD</option>
                <option value="manual">Manual entry</option>
              </select>
            </div>
          </div>
          <div class="btc-field btc-manual-field" id="btc-sp-se-manual-group" style="display:none">
            <label class="btc-label">Manual annual SE income</label>
            <input type="number" id="btc-sp-se-manual" placeholder="Full year estimate" min="0" step="0.01">
          </div>
          <div class="btc-sub-block">
            <div class="btc-sub-title">QBID — SE</div>
            <div class="btc-grid2">
              <div class="btc-field">
                <label class="btc-label">Employee salaries (annual)</label>
                <input type="number" id="btc-sp-se-emp-salary" placeholder="e.g. 0" min="0" step="0.01">
              </div>
              <div class="btc-field">
                <label class="btc-label">SE health insurance deduction</label>
                <input type="number" id="btc-sp-se-health" placeholder="e.g. 0" min="0" step="0.01">
              </div>
            </div>
            <div class="btc-sub-title btc-sstb-label">SSTB status</div>
            <div class="btc-sstb-row">
              <button type="button" class="btc-sstb-btn active" data-target="btc-sp-se-sstb" data-val="non_sstb">Non-SSTB</button>
              <button type="button" class="btc-sstb-btn" data-target="btc-sp-se-sstb" data-val="sstb">SSTB</button>
            </div>
            <input type="hidden" id="btc-sp-se-sstb" value="non_sstb">
          </div>
        </div>

        <!-- Spouse S-Corp block -->
        <div class="btc-income-block" id="btc-sp-scorp" style="display:none">
          <div class="btc-income-block-title">S-Corporation</div>
          <div class="btc-grid2">
            <div class="btc-field">
              <label class="btc-label">S-Corp net income YTD <span class="btc-req">*</span></label>
              <input type="number" id="btc-sp-scorp-income" placeholder="e.g. 0" min="0" step="0.01">
            </div>
            <div class="btc-field">
              <label class="btc-label">Annual income option</label>
              <select id="btc-sp-scorp-annual-option">
                <option value="annualize">Annualize YTD</option>
                <option value="manual">Manual entry</option>
              </select>
            </div>
          </div>
          <div class="btc-field btc-manual-field" id="btc-sp-scorp-manual-group" style="display:none">
            <label class="btc-label">Manual annual S-Corp income</label>
            <input type="number" id="btc-sp-scorp-manual" placeholder="Full year estimate" min="0" step="0.01">
          </div>
          <div class="btc-sub-block">
            <div class="btc-sub-title">QBID — S-Corp</div>
            <div class="btc-grid3-tight">
              <div class="btc-field">
                <label class="btc-label">Owner gross salary</label>
                <input type="number" id="btc-sp-scorp-owner-salary" placeholder="e.g. 0" min="0" step="0.01">
              </div>
              <div class="btc-field">
                <label class="btc-label">Owner pre-tax contributions</label>
                <input type="number" id="btc-sp-scorp-owner-pretax" placeholder="e.g. 0" min="0" step="0.01">
              </div>
              <div class="btc-field">
                <label class="btc-label">Employee salaries (annual)</label>
                <input type="number" id="btc-sp-scorp-emp-salary" placeholder="e.g. 0" min="0" step="0.01">
              </div>
            </div>
            <div class="btc-field" style="margin-top:10px">
              <label class="btc-label">S-Corp SE health insurance deduction</label>
              <input type="number" id="btc-sp-scorp-health" placeholder="e.g. 0" min="0" step="0.01">
            </div>
            <div class="btc-sub-title btc-sstb-label" style="margin-top:12px">SSTB status</div>
            <div class="btc-sstb-row">
              <button type="button" class="btc-sstb-btn active" data-target="btc-sp-scorp-sstb" data-val="non_sstb">Non-SSTB</button>
              <button type="button" class="btc-sstb-btn" data-target="btc-sp-scorp-sstb" data-val="sstb">SSTB</button>
            </div>
            <input type="hidden" id="btc-sp-scorp-sstb" value="non_sstb">
          </div>
        </div>
      </div><!-- /spouse -->
    </div><!-- /business-cols -->

    <!-- Add spouse toggle (MFJ only) -->
    <div id="btc-spouse-toggle-wrap" style="display:none; margin-top:1rem;">
      <label class="btc-toggle-label">
        <span class="btc-toggle-switch">
          <input type="checkbox" id="btc-spouse-toggle">
          <span class="btc-toggle-slider"></span>
        </span>
        Spouse also has business income
      </label>
    </div>
  </div>

  <!-- ── SECTION 4: Other household income ──────────────────────────── -->
  <div class="btc-section">
    <div class="btc-section-head">
      <span class="btc-section-num">4</span>
      <div>
        <div class="btc-section-title">Other household income <span class="btc-opt-badge">optional</span></div>
        <div class="btc-section-sub">Annual estimates — wages, investments, rental, retirement</div>
      </div>
    </div>
    <div class="btc-grid3">
      <div class="btc-field btc-spouse-wages-field" id="btc-spouse-wages-wrap" style="display:none">
        <label class="btc-label" for="btc-spouse-wages">Spouse W-2 wages</label>
        <input type="number" id="btc-spouse-wages" placeholder="e.g. 0" min="0" step="0.01">
      </div>
      <div class="btc-field">
        <label class="btc-label" for="btc-interest-divs">Interest &amp; dividends</label>
        <input type="number" id="btc-interest-divs" placeholder="e.g. 0" min="0" step="0.01">
      </div>
      <div class="btc-field">
        <label class="btc-label" for="btc-st-gains">Short-term capital gains</label>
        <input type="number" id="btc-st-gains" placeholder="e.g. 0" step="0.01">
      </div>
      <div class="btc-field">
        <label class="btc-label" for="btc-lt-gains">Long-term capital gains
          <span class="btc-tooltip" data-tip="Taxed at preferential 0%/15%/20% rates — taxed separately from ordinary income">?</span>
        </label>
        <input type="number" id="btc-lt-gains" placeholder="e.g. 0" step="0.01">
      </div>
      <div class="btc-field">
        <label class="btc-label" for="btc-rental">Net rental income</label>
        <input type="number" id="btc-rental" placeholder="e.g. 0" step="0.01">
      </div>
      <div class="btc-field">
        <label class="btc-label" for="btc-retirement">Retirement distributions</label>
        <input type="number" id="btc-retirement" placeholder="e.g. 0" min="0" step="0.01">
      </div>
      <div class="btc-field">
        <label class="btc-label" for="btc-other-income">Other income</label>
        <input type="number" id="btc-other-income" placeholder="e.g. 0" step="0.01">
      </div>
    </div>
  </div>

  <!-- ── SECTION 5: Deductions ───────────────────────────────────────── -->
  <div class="btc-section">
    <div class="btc-section-head">
      <span class="btc-section-num">5</span>
      <div>
        <div class="btc-section-title">Deductions</div>
        <div class="btc-section-sub">Standard or itemized, plus above-the-line deductions</div>
      </div>
    </div>
    <div class="btc-grid2" style="margin-bottom:1rem">
      <div class="btc-field">
        <label class="btc-label" for="btc-ded-type">Deduction type <span class="btc-req">*</span></label>
        <select id="btc-ded-type">
          <option value="standard">Standard deduction</option>
          <option value="itemized">Itemized deductions</option>
        </select>
      </div>
      <div class="btc-field" id="btc-itemized-wrap" style="display:none">
        <label class="btc-label" for="btc-itemized">Itemized deductions total <span class="btc-req">*</span></label>
        <input type="number" id="btc-itemized" placeholder="e.g. 28000" min="0" step="0.01">
      </div>
    </div>
    <div class="btc-divider"></div>
    <div class="btc-sub-section-title">Above-the-line deductions <span class="btc-opt-badge">optional</span></div>
    <div class="btc-grid3">
      <div class="btc-field">
        <label class="btc-label" for="btc-sep-ira">SE SEP IRA / Solo 401(k)</label>
        <input type="number" id="btc-sep-ira" placeholder="e.g. 0" min="0" step="0.01">
      </div>
      <div class="btc-field">
        <label class="btc-label" for="btc-trad-ira">Traditional IRA</label>
        <input type="number" id="btc-trad-ira" placeholder="e.g. 0" min="0" step="0.01">
      </div>
      <div class="btc-field">
        <label class="btc-label" for="btc-other-ded-agi">Other deductions for AGI</label>
        <input type="number" id="btc-other-ded-agi" placeholder="e.g. 0" min="0" step="0.01">
      </div>
    </div>
  </div>

  <!-- ── SECTION 6: Taxes paid & credits ────────────────────────────── -->
  <div class="btc-section">
    <div class="btc-section-head">
      <span class="btc-section-num">6</span>
      <div>
        <div class="btc-section-title">Taxes paid &amp; credits <span class="btc-opt-badge">optional</span></div>
        <div class="btc-section-sub">Withholding and estimated payments made so far this year</div>
      </div>
    </div>
    <div class="btc-grid3">
      <div class="btc-field">
        <label class="btc-label" for="btc-fed-paid">Federal taxes paid YTD
          <span class="btc-tooltip" data-tip="Total withholding + estimated payments through the end of the selected quarter period">?</span>
        </label>
        <input type="number" id="btc-fed-paid" placeholder="e.g. 0" min="0" step="0.01">
      </div>
      <div class="btc-field">
        <label class="btc-label" for="btc-credits-type">Tax credits type</label>
        <select id="btc-credits-type">
          <option value="">None</option>
          <option value="refundable">Refundable</option>
          <option value="non_refundable">Non-refundable</option>
          <option value="both">Both</option>
        </select>
      </div>
      <div class="btc-field" id="btc-refundable-wrap" style="display:none">
        <label class="btc-label" for="btc-refundable">Refundable credits (annual)</label>
        <input type="number" id="btc-refundable" placeholder="e.g. 0" min="0" step="0.01">
      </div>
      <div class="btc-field" id="btc-nonrefundable-wrap" style="display:none">
        <label class="btc-label" for="btc-nonrefundable">Non-refundable credits (annual)</label>
        <input type="number" id="btc-nonrefundable" placeholder="e.g. 0" min="0" step="0.01">
      </div>
    </div>
  </div>

  <!-- ── CALCULATE BUTTON ───────────────────────────────────────────── -->
  <div id="btc-error-msg" class="btc-error" style="display:none"></div>
  <button type="button" id="btc-calc-btn" class="btc-calc-btn">Calculate quarterly estimate</button>

  <!-- ── RESULTS ────────────────────────────────────────────────────── -->
  <div id="btc-results" class="btc-results" style="display:none">

    <div class="btc-results-header">
      <h2 class="btc-results-title">Results</h2>
      <div class="btc-disclaimer">Federal tax only. For state safe-harbor, use the <a href="https://biztaxplaybook.com/tools/estimated-tax-safe-harbor-calculator/" target="_blank">Estimated Tax Safe Harbor</a> tool.</div>
    </div>

    <!-- Payment cards — most prominent -->
    <div class="btc-cards-grid" id="btc-payment-cards">
      <div class="btc-card btc-card-safe-harbor" id="btc-card-sh-wrap">
        <div class="btc-card-label">Safe harbor (prior year)</div>
        <div class="btc-card-amount" id="btc-card-sh">—</div>
        <div class="btc-card-sub" id="btc-card-sh-sub"></div>
      </div>
      <div class="btc-card btc-card-cy-90">
        <div class="btc-card-label">Current year 90% rule</div>
        <div class="btc-card-amount" id="btc-card-cy90">$0</div>
        <div class="btc-card-sub" id="btc-card-cy90-sub"></div>
      </div>
      <div class="btc-card btc-card-cy-full">
        <div class="btc-card-label">Current year (full)</div>
        <div class="btc-card-amount" id="btc-card-cyfull">$0</div>
        <div class="btc-card-sub" id="btc-card-cyfull-sub"></div>
      </div>
    </div>

    <!-- Detail rows -->
    <div class="btc-results-section">
      <div class="btc-results-section-title">Year-end projections</div>
      <div class="btc-result-row"><span>Total income</span><span id="r-total-income">—</span></div>
      <div class="btc-result-row btc-indent"><span>Taxpayer SE income (annual)</span><span id="r-tp-se">—</span></div>
      <div class="btc-result-row btc-indent"><span>Taxpayer S-Corp income (annual)</span><span id="r-tp-scorp">—</span></div>
      <div class="btc-result-row btc-indent" id="r-tp-owner-row"><span>Taxpayer owner salary (taxable)</span><span id="r-tp-owner">—</span></div>
      <div class="btc-result-row btc-indent" id="r-sp-se-row"><span>Spouse SE income (annual)</span><span id="r-sp-se">—</span></div>
      <div class="btc-result-row btc-indent" id="r-sp-scorp-row"><span>Spouse S-Corp income (annual)</span><span id="r-sp-scorp">—</span></div>
      <div class="btc-result-row btc-indent" id="r-sp-owner-row"><span>Spouse owner salary (taxable)</span><span id="r-sp-owner">—</span></div>
      <div class="btc-result-row btc-indent"><span>Taxpayer W-2 earnings</span><span id="r-tp-w2">—</span></div>
      <div class="btc-result-row btc-indent" id="r-spouse-wages-row"><span>Spouse W-2 wages</span><span id="r-spouse-wages">—</span></div>
      <div class="btc-result-row btc-indent"><span>Other income (investments, rental, etc.)</span><span id="r-other">—</span></div>

      <div class="btc-result-divider"></div>
      <div class="btc-result-row"><span>AGI</span><span id="r-agi">—</span></div>
      <div class="btc-result-row btc-indent"><span>1/2 SE tax deduction</span><span id="r-se-ded">—</span></div>
      <div class="btc-result-row btc-indent"><span>Other deductions for AGI</span><span id="r-other-ded">—</span></div>

      <div class="btc-result-divider"></div>
      <div class="btc-result-row btc-strong"><span>Federal taxable income</span><span id="r-taxable">—</span></div>
      <div class="btc-result-row btc-indent"><span id="r-ded-label">Standard deduction</span><span id="r-deduction">—</span></div>
      <div class="btc-result-row btc-indent"><span>QBID</span><span id="r-qbid">—</span></div>
      <div class="btc-result-row btc-indent" id="r-qbid-tp-se-row"><span>&nbsp;&nbsp;Taxpayer SE QBID</span><span id="r-qbid-tp-se">—</span></div>
      <div class="btc-result-row btc-indent" id="r-qbid-tp-scorp-row"><span>&nbsp;&nbsp;Taxpayer S-Corp QBID</span><span id="r-qbid-tp-scorp">—</span></div>
      <div class="btc-result-row btc-indent" id="r-qbid-sp-se-row"><span>&nbsp;&nbsp;Spouse SE QBID</span><span id="r-qbid-sp-se">—</span></div>
      <div class="btc-result-row btc-indent" id="r-qbid-sp-scorp-row"><span>&nbsp;&nbsp;Spouse S-Corp QBID</span><span id="r-qbid-sp-scorp">—</span></div>
    </div>

    <div class="btc-results-section">
      <div class="btc-results-section-title">Annual tax liabilities</div>
      <div class="btc-result-row btc-strong"><span>Total liability</span><span id="r-total-liability">—</span></div>
      <div class="btc-result-row btc-indent"><span>Federal income tax</span><span id="r-fed-income-tax">—</span></div>
      <div class="btc-result-row btc-indent" id="r-tp-se-tax-row"><span>Taxpayer SE tax</span><span id="r-tp-se-tax">—</span></div>
      <div class="btc-result-row btc-indent" id="r-sp-se-tax-row"><span>Spouse SE tax</span><span id="r-sp-se-tax">—</span></div>
      <div class="btc-result-row btc-indent"><span>Additional Medicare tax (0.9%)</span><span id="r-add-medicare">—</span></div>
      <div class="btc-result-row btc-indent"><span>Net investment income tax (NIIT 3.8%)</span><span id="r-niit">—</span></div>

      <div class="btc-result-divider"></div>
      <div class="btc-result-row"><span>Refundable credits</span><span id="r-refundable">—</span></div>
      <div class="btc-result-row"><span>Non-refundable credits</span><span id="r-nonrefundable">—</span></div>
      <div class="btc-result-row btc-strong"><span>Net tax due</span><span id="r-net-tax-due">—</span></div>

      <div class="btc-result-divider"></div>
      <div class="btc-result-row"><span>Marginal tax rate</span><span id="r-marginal">—</span></div>
      <div class="btc-result-row"><span>Effective tax rate</span><span id="r-effective">—</span></div>
    </div>

    <div class="btc-results-section">
      <div class="btc-results-section-title">Quarter summary</div>
      <div class="btc-result-row"><span>Federal taxes paid YTD</span><span id="r-fed-paid">—</span></div>
      <div class="btc-result-row" id="r-sh-row"><span>Safe harbor target (prior year × multiplier)</span><span id="r-sh-target">—</span></div>
      <div class="btc-result-row"><span>Current year estimated tax (cumulative)</span><span id="r-cy-target">—</span></div>
    </div>

  </div><!-- /results -->

</div><!-- /btc-wrap -->
