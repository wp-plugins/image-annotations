=== Image Annotations ===
Contributors: M03Gen
Donate link: http://m03g.guriny.ru/image-annotations/
Tags: images, note, annotations, comments
Requires at least: 3.8.1
Tested up to: 4.3
Stable tag: 1.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Image Annotations plugin lets readers to leave annotations to the selected area of the image in comments.

== Description ==

Image Annotations plugin lets readers to leave annotations to the selected area of the image in comments. Important: for now the plugin works only with [Comment Images](https://wordpress.org/plugins/comment-images/) plugin (by Tom McFarlin).

Readers can switch off the visibility of the selections as well as control the display of the comments. Only authorized users can leave annotations (also user can delete his own annotations).


Плагин Image Annotations позволяет читателям оставлять аннотации к выделенной области на изображении в комментариях. Важно: на данный момент плагин работает только с плагином [Comment Images](https://wordpress.org/plugins/comment-images/).

Читатели могут контролировать видимость выделенных областей на изображении и включать и выключать отображение комментариев. Только зарегистрированные пользователи могут оставлять аннотации (также пользователь может удалить свою аннотацию).

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. It work!

Important: for now the plugin works only with [Comment Images](https://wordpress.org/plugins/comment-images/) plugin (by Tom McFarlin).

== Screenshots ==

1. Выделение необходимого фрагмента на изображении, под которым появляется форма для ввода комментария.
2. Два уже добавленных комментария от одного автора. При наведении на комментарий, появляется возможность (у автора и администраторов) его удалить.
3. При наведении на комментарий, подсвечивается выделение. И наоборот.
4. У каждого автора свой цвет комментария и выделения.
5. Иконка в правом нижнем углу изображения (видимые при наведении) позволяет скрыть/показать аннотации.
6. Иконка в правом верхнем углу изображения (видимые при наведении) позволяет скрыть/показать выделения.

== Changelog ==

= 1.0.4 =

* Added ability to view comments when hovering the selection area.
* Icons are now a font.
* Some style changes.
* Minor bug fixes.

* Добавлена возможность просмотра комментария при наведение на выделение
* Изображения иконок заменены на шрифт
* Изменены стили
* Исправлены ошибки

= 1.0.3 =

* Added the ability to edit comments (15 minutes after publication)
* Added page with a list of comments in the admin panel
* Bug fix
* Visual and logical improvement

* Добавлена возможность редактирования комментария (в течение 15 минут с момента публикации)
* Добавлена страница с полным списком комментариев в Панели администратора
* Исправлены ошибки (в том числе ошибка с некорректным сохранением положения и размеров выделения)
* Произведеные различные улучшения визуального и логического характера

= 1.0.2 =

* Bug fixes

* Исправлены ошибки. Теперь плагин должен корректно работать с любой темой WordPress и при любом масштабе. Старые комментарии придётся либо удалить, либо обновить (возможность появится позже)

= 1.0.1 =

* Bug fix. Past comments will have to delete :( 
* Added Russian and English
* Added color for the frames and comments
* Added smooth animation

* Устранена ошибка, из-за которой комментарии с кавычками не сохранялись (к сожалению, прошлые комментарии придётся удалить, так как они будут выводиться некорректно)
* Добавлена поддержка английского и русского языков
* Добавлены цвета рамок выделений и комментариев - у каждого пользователя свой цвет, основанный на его нике
* Добавлена плавная анимация для некоторых действий

= 1.0.0 =

* First version