<?php
/**
 * @property WithRelatedBehavior $withRelated
 * @property array $preSetAttributes
 */
class QActiveRecord extends CActiveRecord
{
    /**
     * @author Ivan Chelishchev <chelishchev@gmail.com>
     * @var string Формат даты сохранения в БД
     */
    public $dateFormatSave    = 'yyyy-MM-dd HH:mm:ss';
    /**
     * @author Ivan Chelishchev <chelishchev@gmail.com>
     * @var string Формат просмотра даты
     */
    public $dateFormatDisplay = 'dd.MM.yyyy HH:mm:ss';


    public function behaviors()
    {
        Yii::import('common.extensions.behaviors.wr.*');
        return array(
            'withRelated' => array(
                'class' => 'WithRelatedBehavior',
            ),
        );
    }

    protected $preSetAttributes = array();

    /**
     * Получение строки с ошибками по всем атрибутам.
     * @param string $del
     * @return string
     * @author Ivan Chelishchev <chelishchev@gmail.com>
     */
    public function getStringWithAllErrors($del = '<br/>')
    {
        $er  = $this->getErrors();
        $s = array();
        foreach ($er as $attrErr)
        {
            $s[] = implode($del, $attrErr);
        }
        unset($attrErr);

        return implode($del, $s);
    }

    /**
     * Метод подгрузки одного экземпляра модели по параметрам,
     * переданным в массиве id
     *
     * @param mixed $id Массив в формате поле => значение
     * @param array $with Массив связей для жадной загрузки
     * @param string $target Категория для метода Yii::t()
     * @param string $msg Сообщение для метода Yii::t()
     * @return $this
     * @throws CHttpException В случае отсутсвия модели в БД
     * @author Ivan Chelishchev <chelishchev@gmail.com>
     */
    public static function load(
        $id,
        $with   = array(),
        $target = 'core',
        $msg    = 'Запрошенный элемент отсутствует в базе.')
    {

        $model = null;
        if(!is_array($id))
        {
            $model = static::model()->with($with)->findByPk($id);
        }
        else
        {
            $model = static::model()->with((array)$with)->findByAttributes($id);
        }

        if($model === null)
        {
            throw new CHttpException(404, Yii::t($target, $msg));
        }

        return $model;
    }

    /**
     * Логика получения отформатированной даты для показа юзерам
     * @author Ivan Chelishchev <chelishchev@gmail.com>
     * @param $date
     * @return string
     */
    public function convertDateToDisplay($date)
    {
        return Yii::app()->dateFormatter->format($this->dateFormatDisplay, CDateTimeParser::parse($date, $this->dateFormatSave));
    }

    /**
     * Логика получения отформатированной даты для сохранения в БД
     * @author Ivan Chelishchev <chelishchev@gmail.com>
     * @param $date
     * @return string
     */
    public function convertDateToSave($date)
    {
        return Yii::app()->dateFormatter->format($this->dateFormatSave, CDateTimeParser::parse($date, $this->dateFormatDisplay));
    }

    public function convertDateFromTimestamp($timestamp, $default = null)
    {
        return $this->datetime? Yii::app()->dateFormatter->format($this->dateFormatDisplay, $timestamp) : $default;
    }

    public function convertDateToTimestamp($date)
    {
        return CDateTimeParser::parse($date, $this->dateFormatDisplay);
    }


    /**
     * Установка атрибутов, которые считаются предустановленными и перепишут входящие из формы при случае
     * @param array $attr
     * @return QActiveRecord
     */
    public function setPreSetAttributes(array $attr = array())
    {
        $this->preSetAttributes = $attr;

        return $this;
    }

    public function getPreSetAttributes()
    {
        return $this->preSetAttributes;
    }

    /**
     * Проверка установки предустановленного атрибута
     * Проверка идет через isset()!!!
     * @param $name
     * @return bool
     */
    public function isPreSetAttribute($name)
    {
        return isset($this->preSetAttributes[$name]);
    }

    public function setAttributes($values, $safeOnly = true)
    {
        //всё как раньше
        parent::setAttributes($values, $safeOnly);
        //а вот тут сверху перепишем предустановленными нами атрибутами, плюя на safe
        parent::setAttributes($this->getPreSetAttributes(), true);
    }

    /**
     * Считаем, что таким образом будут запрашиваться значение
     * $model('person.user.id')
     *
     * @param $valuePath
     * @param $default
     * @return mixed
     */
    public function __invoke($valuePath, $default = null)
    {
        try
        {
            return CHtml::value($this, trim($valuePath, '.'), $default);
        }
        catch(Exception $e)
        {
            return $default;
        }
    }

    /**
     * Scope (именованная группа) для получение последнего созданного элемента
     * @param null $alias
     * @return $this
     */
    public function lastCreated($alias = null)
    {
        $alias === null && $alias = $this->tableAlias;
        $alias = trim($alias, '.') . '.';
        $this->getDbCriteria()->mergeWith(array(
                                               'order' => $alias . 'created DESC',
                                               'limit' => 1,
                                          ));

        return $this;
    }

    /**
     * Проверяет имеют ли два объетка одинаковые атрибуты
     * @param CActiveRecord $a
     * @param array $attr список атрибутов, по которым будет осуществляться сравнение
     * @return bool
     */
    public function equalsAttributes(CActiveRecord $a, array $attr = array())
    {
        !$attr && $attr = true;
        return (bool)array_intersect_assoc($this->getAttributes($attr), $a->getAttributes($attr));
    }
}
