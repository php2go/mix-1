<?php
# Generated by the protocol buffer compiler (https://github.com/mix-php/grpc). DO NOT EDIT!
# source: greeter.proto

namespace Php\Micro\Grpc\Greeter;

use Mix\Grpc;
use Mix\Context\Context;

class SayClient extends Grpc\Client\AbstractClient
{
    /**
    * @param Context $context
    * @param Request $request
    * @param array $options
    * @return Response
    *
    * @throws Grpc\Exception\InvokeException
    */
    public function Hello(Context $context, Request $request, array $options = []): Response
    {
        return $this->_simpleRequest('/php.micro.grpc.greeter.Say/Hello', $context, $request, new Response(), $options);
    }
}
