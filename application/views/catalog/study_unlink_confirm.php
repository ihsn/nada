<div style="padding:10px;">
<h1>Unlink study</h1>
<?php
if ($result!==FALSE)
{
    $content='Study link was removed successfully!';
}
else
{
    $content='Error: Failed to remove study link';
}
?>
<p>
<?php echo $content;?>
</p>
</div>