=== Image Annotations ===
Contributors: M03Gen
Donate link: http://m03g.guriny.ru/image-annotations/
Tags: images, note, annotations, comments
Requires at least: 3.8.1
Tested up to: 4.1
Stable tag: 1.03
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Image Annotations plugin lets readers to leave annotations to the selected area of the image in comments.

== Description ==

Image Annotations plugin lets readers to leave annotations to the selected area of the image in comments. Important: for now the plugin works only with [Comment Images](https://wordpress.org/plugins/comment-images/) plugin (by Tom McFarlin).

Readers can switch off the visibility of the selections as well as control the display of the comments. Only authorized users can leave annotations (also user can delete his own annotations).


Плагин Image Annotations позволяет читателям оставлять аннотации к выделенной области на изображении в комментариях. Важно: на данный момент плагин работает только с плагином [Comment Images](https://wordpress.org/plugins/comment-images/).

Читатели могут контролировать видимость выделенных областей на изображении и включать и выключать отображение комментариев. Только зарегистрированные пользователи могут оставлять аннотации (также пользователь может удалить свою аннотацию).

[Подробное описание возможностей](http://m03g.guriny.ru/image-annotations/)

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. It work!

Important: for now the plugin works only with [Comment Images](https://wordpress.org/plugins/comment-images/) plugin (by Tom McFarlin).

== Screenshots ==

1. Выделение необходимого фрагмента на изображении, под которым появляется форма для ввода комментария.
2. При наведении на комментарий, появляется возможность его отредактировать (у автора) и удалить (у автора и администраторов).
3. Форма редактирования комментария. Таймер времени возможности редактирования.
4. При наведении на комментарий, подсвечивается выделение. И наоборот.
5. У каждого автора свой цвет комментария и выделения.
6. Иконка в правом нижнем углу изображения (видимые при наведении) позволяет скрыть/показать аннотации.
7. Иконка в правом верхнем углу изображения (видимые при наведении) позволяет скрыть/показать выделения.
8. Список аннотаций в Панели администратора.

== Changelog ==

= 1.03 =

* Added the ability to edit comments (15 minutes after publication)
* Added page with a list of comments in the admin panel
* Bug fix
* Visual and logical improvement

* Добавлена возможность редактирования комментария (в течение 15 минут с момента публикации)
* Добавлена страница с полным списком комментариев в Панели администратора
* Исправлены ошибки (в том числе ошибка с некорректным сохранением положения и размеров выделения)
* Произведеные различные улучшения визуального и логического характера

= 1.02 =

* Bug fix

* Исправлены ошибки. Теперь плагин должен корректно работать с любой темой WordPress и при любом масштабе. Старые комментарии придётся либо удалить, либо обновить (возможность появится позже)

= 1.01 =

* Bug fix. Past comments will have to delete :( 
* Added Russian and English
* Added color for the frames and comments
* Added smooth animation

* Устранена ошибка, из-за которой комментарии с кавычками не сохранялись (к сожалению, прошлые комментарии придётся удалить, так как они будут выводиться некорректно)
* Добавлена поддержка английского и русского языков
* Добавлены цвета рамок выделений и комментариев - у каждого пользователя свой цвет, основанный на его нике
* Добавлена плавная анимация для некоторых действий

= 1.00 =

* First version