<?php


use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;



function cryptoDecrypt($secret) {
    $key = safeKey();
    return Crypto::decrypt($secret, $key);
}

function cryptoEncrypt($secret) {
    $key = safeKey();
    return Crypto::encrypt($secret, $key);
}


function safeKey() {
    return Key::loadFromAsciiSafeString(CHARLIE_CRYPTO_KEY);
}
