<script type="text/javascript" src="https://pym.nprapps.org/pym.v1.min.js"></script>

<div class="container widgets-container">
    <h1><a target="_blank" href="<?php echo $widget['link'];?>"><?php echo $widget['title'];?></a></h1>
    <p><?php echo nl2br($widget['description']);?></p>    

    <div id="widget-<?php echo $widget['uuid'];?>"></div>
    <script>
        var pymParent = new pym.Parent('widget-<?php echo $widget['uuid'];?>', '<?php echo site_url('widgets/embed/'.$widget['uuid']);?>', {});
    </script>
</div>