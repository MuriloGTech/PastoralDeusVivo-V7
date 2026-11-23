<?php if (!empty($flash)): ?>
<script>
    Swal.fire({
        icon: <?= json_encode($flash['tipo']) ?>,
        title: <?= json_encode($flash['tipo'] === 'success' ? 'Pronto!' : 'Ops...') ?>,
        text: <?= json_encode($flash['texto']) ?>,
        confirmButtonColor: '#2980b9'
    });
</script>
<?php endif; ?>
