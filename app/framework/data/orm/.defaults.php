<?php

use data\orm\cache\TransformationsCache as TC;

TC::set("md5", "data\\orm\\transformation\\Md5Transformation");
TC::set("sha1", "data\\orm\\transformation\\Sha1Transformation");
TC::set("json", "data\\orm\\transformation\\JsonTransformation");
TC::set("serialize", "data\\orm\\transformation\\PhpSerializationTransformation");
TC::set("zip", "data\\orm\\transformation\\ZipTransformation");
