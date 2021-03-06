<?php
define('O_NAME_KEY', '--N');
define('O_GCC_FLAGS_KEY', '--FLAGS');
define('O_HEADERS_KEY', '--I');
define('O_SOURCES_KEY', '--S');
define('O_DEPENDENCIES_KEY', '--D');
define('O_LIBRARY_KEY', '--L');

/**
 * Makefile patterns
 */
define('_MAKEFILE_', 'Makefile');
define(
    'MAKEFILE_COPYRIGHT', 
    '########################################################' . PHP_EOL .
    '#@@@-------------GENERATED BY AUTO_MAKE-------------@@@#' . PHP_EOL .
    '#@@                                                  @@#' . PHP_EOL .
    '#@  https://github.com/kagestonedragon/auto_make_php  @#' . PHP_EOL .
    '########################################################' . PHP_EOL . PHP_EOL
);
define('MAKEFILE_NAME', 'NAME = #NAME#' . PHP_EOL);
define('MAKEFILE_GCC', 'GCC = gcc' . PHP_EOL);
define('MAKEFILE_GCC_FLAGS', 'GCC_FLAGS = #GCC_FLAGS#' . PHP_EOL);
define('MAKEFILE_OBJ_PATH', 'OBJ_PATH = objects/' . PHP_EOL);
define('MAKEFILE_INCLUDE_PATH', 'INCLUDE_#INCLUDE_UPPER# = #INCLUDE_LOWER#');
define('MAKEFILE_SOURCES_PATH', '#DIRECTORY_UPPER#_PATH = #DIRECTORY_LOWER#/' . PHP_EOL);
define('MAKEFILE_SOURCES_FILES', '#DIRECTORY_UPPER#_FILES = #DIRECTORY_FILES#' . PHP_EOL);
define('MAKEFILE_OBJ_CORE', 'OBJ_#DIRECTORY_UPPER#_CORE = $(addprefix $(OBJ_PATH), $(#DIRECTORY_UPPER#_FILES:.c=.o))' . PHP_EOL);
define('MAKEFILE_OBJECTS', 'OBJECTS = ');
define('MAKEFILE_ALL', 'all: $(NAME)' . PHP_EOL);
define(
    'MAKEFILE_ALL_NAME', 
    '$(NAME): $(OBJECTS)' .
    '#DEPENDENCIES#' . 
    "\t\t" . '@($(GCC) $(GCC_FLAGS) -o $(NAME) $(OBJECTS) #LIBRARIES#)' . PHP_EOL .
    "\t\t" . '@(echo "^[[0;92m"$(NAME) "was created!^[[0;0m")' . PHP_EOL
);
define(
    'MAKEFILE_CLEAN',
    'clean:' . PHP_EOL .
    '#DEPENDENCIES#' .
    "\t\t" . '@(/bin/rm -rf $(OBJ_PATH))' . PHP_EOL .
    "\t\t" . '@(echo "^[[0;91mAll object files have been deleted!^[[0;0m")' . PHP_EOL
);
define(
    'MAKEFILE_FCLEAN',
    'fclean: clean' . PHP_EOL .
    '#DEPENDENCIES#' .
    "\t\t" . '@(/bin/rm -f $(NAME))' . PHP_EOL .
    "\t\t" . '@(echo "^[[0;91m"$(NAME) "has been deleted!^[[0;0m")' . PHP_EOL
);
define(
    'MAKEFILE_RE',
    're: fclean all' . PHP_EOL
);
define('MAKEFILE_OBJECTS_RULE', 'objects:' . PHP_EOL . "\t\t" . '@(mkdir $(OBJ_PATH))' . PHP_EOL);
define(
    'MAKEFILE_OBJECTS_O',
    'objects/%.o: $(#DIRECTORY_UPPER#)%.c | objects' . PHP_EOL .
    "\t\t" . '@($(GCC) $(GCC_FLAGS)#INCLUDES# -c $< -o $@)' . PHP_EOL .
    "\t\t" . '(echo "^[[0;93m"$< "->" $@"^[[0;0m")' . PHP_EOL . PHP_EOL
);