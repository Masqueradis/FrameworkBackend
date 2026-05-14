document.addEventListener('DOMContentLoaded', function(){
    const filterForm = document.getElementById('filter-form');
    if(!filterForm) return;

    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        }
    }

    function triggerSubmit() {
        const allInputs = filterForm.querySelectorAll('input');
        allInputs.forEach((input) => {
            if(input.value.trim() === '') {
                input.disabled = true;
            }
        });
        filterForm.submit();
    }

    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function () {
            triggerSubmit();
        }, 600));
    }

    const checkboxes = filterForm.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(box => {
        box.addEventListener('change', () => {
            triggerSubmit();
        });
    });

    const priceInputs = filterForm.querySelectorAll('input[type="number"]');
    priceInputs.forEach((input) => {
        input.addEventListener('blur', () => {
            triggerSubmit();
        });

        input.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                triggerSubmit();
            }
        });
    });

    filterForm.addEventListener('submit', function() {
        const allInputs = filterForm.querySelectorAll('input');
        allInputs.forEach((input) => {
            if(input.value.trim() === '') {
                input.disabled = true;
            }
        });
    });
});
