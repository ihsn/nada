<?php if (!empty($schema_org_json_ld)) : ?>
<script type="application/ld+json">
    <?php echo json_encode($schema_org_json_ld, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES + JSON_HEX_TAG + JSON_HEX_APOS); ?>
</script>
<?php endif; ?>
