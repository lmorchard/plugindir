<?php
    $screen_name = $profile->screen_name;
    $h_screen_name = html::specialchars($screen_name);
    $u_screen_name = rawurlencode($screen_name);
?>
<?php
usort($sections, create_function(
    '$b,$a', '
        $a = @$a["priority"];
        $b = @$b["priority"];
        return ($a==$b)?0:(($a<$b)?-1:1);
    '
))
?>
<h3>Profile settings for <a href="<?=url::base().'profiles/'.$u_screen_name?>"><?=$h_screen_name?></a></h3>
<ul class="sections">
    <?php foreach ($sections as $section): ?>
        <li class="section">
            <h3><?= html::specialchars($section['title']) ?></h3>
            <dl>
                <?php foreach ($section['items'] as $item): ?>
                    <dt><a href="<?= url::base() . html::specialchars($item['url']) ?>"><?= html::specialchars($item['title']) ?></a></dt>
                    <dd><?= $item['description'] ?></dd>
                <?php endforeach ?>
            </dl>
        </li>
    <?php endforeach ?>
</ul>
