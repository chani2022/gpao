<?php
namespace App\DataTransformer;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


class DateToStringTransformer implements DataTransformerInterface{
    //put your code here
    public function reverseTransform($strDate = null) {
        return $strDate? (new \DateTime($strDate))->format("Y-m-d"): null;
    }

    public function transform($date) {
        return $date;
    }

}
