<script type="text/javascript">
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('referral-level')) {
            e.preventDefault();
            window.location.href = e.target.getAttribute('href');
        }
    });
</script>
