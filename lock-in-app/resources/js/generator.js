
const slider = document.getElementById('entropyRange');
const value = document.getElementById('rangeValue');

function updateSlider() {
    const min = slider.min;
    const max = slider.max;
    const val = slider.value;

    const percent = ((val - min) / (max - min)) * 100;

    console.log(slider)
    console.log(value)

    slider.style.background = `linear-gradient(
        to right,
        #ffffff 0%,
        #ffffff ${percent}%,
        #3a3a3a ${percent}%,
        #3a3a3a 100%
    )`;

value.textContent = val;
}

slider.addEventListener('input', updateSlider);

// init
updateSlider();
