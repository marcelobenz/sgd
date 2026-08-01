@once
<script>
document.addEventListener('DOMContentLoaded', function () {
    function updateOther(select) {
        const target = document.getElementById(select.dataset.otherTarget);
        if (!target) return;
        const show = Array.from(select.selectedOptions).some(option => option.value === '__otro__');
        target.classList.toggle('d-none', !show);
        const input = target.querySelector('input, textarea');
        if (input) {
            input.disabled = !show;
            input.required = show && select.hasAttribute('required');
            if (show) input.focus();
        }
    }

    document.querySelectorAll('[data-other-target]').forEach(function (select) {
        select.addEventListener('change', function () { updateOther(select); });
        updateOther(select);
    });
});
</script>
@endonce
