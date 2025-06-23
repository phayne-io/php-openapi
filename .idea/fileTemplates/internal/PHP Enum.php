<?php

#parse("PHP File Header.php")

#if (${NAMESPACE})
namespace ${NAMESPACE};
#end

#parse("PHP Enum Doc Comment.php")
enum ${NAME}#if (${BACKED_TYPE}) : ${BACKED_TYPE} #end
{

}
