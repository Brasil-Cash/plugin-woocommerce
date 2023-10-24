<?php 

namespace Bcpag\Interfaces;

interface PaymentInterface {
    /**
     * @return string
     */
    public function getMethodCode();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return array
     */
    public function getRequirementsData();
}