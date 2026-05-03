<div class="card">
    <div class="row">
        <label>PASSWORD LENGTH</label>
        <span class="value" id="lengthValue">32</span>
    </div>

    <input id="passwordLength" class="slider" type="range" min="4" max="128" value="32">

    <div class="range-legend">
        <span>4</span>
        <span>128</span>
    </div>

    <h3 style="margin-top: 32px;">CHARACTER SETS</h3>

    <div class="checkbox-grid">

        <label class="checkbox-card">
            <input id="charUppercase" type="checkbox" checked data-charset="uppercase">
            <div class="checkbox-box"></div>

            <span class="label-text">UPPERCASE</span>
            <span class="label-hint">A-Z</span>
        </label>

        <label class="checkbox-card">
            <input id="charLowercase" type="checkbox" checked data-charset="lowercase">
            <div class="checkbox-box"></div>

            <span class="label-text">LOWERCASE</span>
            <span class="label-hint">a-z</span>
        </label>

        <label class="checkbox-card">
            <input id="charNumbers" type="checkbox" checked data-charset="numbers">
            <div class="checkbox-box"></div>

            <span class="label-text">NUMBERS</span>
            <span class="label-hint">0-9</span>
        </label>

        <label class="checkbox-card">
            <input id="charSymbols" type="checkbox" checked data-charset="symbols">
            <div class="checkbox-box"></div>

            <span class="label-text">SYMBOLS</span>
            <span class="label-hint">!@#$%^&*</span>
        </label>

    </div>

    <label class="checkbox-card" style="margin-top: 16px;">
        <input id="excludeAmbiguous" type="checkbox">
        <div class="checkbox-box"></div>

        <span class="label-text">EXCLUDE AMBIGUOUS</span>
        <span class="label-hint">il1Lo0O</span>
    </label>
</div>
