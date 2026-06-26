<?php if (!empty($schema_org_json_ld)) : ?>
<script type="application/ld+json">
    <?php echo json_encode($schema_org_json_ld, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES); ?>
</script>
<?php endif; ?>
