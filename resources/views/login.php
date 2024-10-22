<?php
/**
 * @var \App\Http\LoginForm $form
 */
?>
<h1>Login</h1>

<form action="/auth/login" method="post">
    <div>
        <label for="email">Email: </label>
        <input type="email" id="email" name="email" <?= $form->getHtmlValidationAttrs('email') ?>/>
    </div>

    <div>
        <label for="password">Password: </label>
        <input type="password" id="password" name="password" <?= $form->getHtmlValidationAttrs('password') ?>/>
    </div>

    <div>
        <input type="submit" value="Login"/>
    </div>
</form>