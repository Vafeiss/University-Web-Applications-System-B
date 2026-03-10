<?php

class Referral {

    public static function generateCode() {
        return strtoupper(substr(md5(uniqid()),0,8));
    }

}