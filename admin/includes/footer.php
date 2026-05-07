        </div>
    </main>
</div>
<script>
document.querySelectorAll('[data-toggle-details]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var target = document.getElementById(this.getAttribute('data-toggle-details'));
        if (target && target.tagName === 'DETAILS') {
            target.open = true;
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>
</body>
</html>
