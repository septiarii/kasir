<?php
// includes/footer.php
?>
    </main>
    <footer style="text-align:center;padding:0.6rem;color:#6c757d;font-size:0.65rem;border-top:1px solid #eee;margin-top:auto;background:white;">
        <p>&copy; <?php echo date('Y'); ?> Hermas POS | <span style="color:#999;">v1.0</span></p>
    </footer>
    <script>
    document.querySelectorAll('.alert').forEach(function(el) {
        setTimeout(function() {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(function() { el.remove(); }, 500);
        }, 4000);
    });
    </script>
</body>
</html>