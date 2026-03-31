document.addEventListener('DOMContentLoaded', function(){
    const filterForm = document.getElementById('filter-form');

    if(!filterForm) return;

    const checkboxes = filterForm.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(box => {
        box.addEventListener('change', () => {
            filterForm.submit();
        });
    });

    const priceInputs = filterForm.querySelectorAll('input[type="number"]');
    priceInputs.forEach((input) => {
        input.addEventListener('blur', () => {
            filterForm.submit();
        });

        input.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterForm.submit();
            }
        });
    });

    filterForm.addEventListener('submit', function() {
        const allInputs = filterForm.querySelectorAll('input');

        allInputs.forEach((input) => {
            if((input.type === 'number' || input.type === 'text' || input.type === 'hidden')&& input.value === '') {
                input.removeAttribute('name');
            }
        })
    })
})
