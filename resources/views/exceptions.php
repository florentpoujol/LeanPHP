<?php
/**
 * @var Throwable $exception
 */
?>

<h1>There was an exception</h1>

<h2><?= get_class($exception) ?></h2>

<ul>
    <li>File: <?= $exception->getFile() ?></li>
    <li>Line: <?= $exception->getLine() ?></li>
    <li>Code: <?= $exception->getCode() ?></li>
</ul>

<h3>Message</h3>

<p>
    <?= $exception->getMessage() ?>
</p>

<h3>Trace</h3>

<pre><?php var_dump($exception->getTrace()); ?></pre>