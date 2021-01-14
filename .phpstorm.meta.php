<?php

namespace PHPSTORM_META {
    override(\Neat\Service\Container::get(), map(['' => '@']));
    override(\Neat\Service\Container::getOrCreate(), map(['' => '@']));
}
