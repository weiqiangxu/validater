<?php

namespace xuweiqiang\validator;

/**
 * DateTimeValidator
 * @author wytanxu@tencent.com
 */
class DateTimeValidator implements BaseValidator
{

    /**
     * 校验出的错误信息
     *
     * @var array
     */
    public $error = array();

    /**
     * 原始数组
     *
     * @var array
     */
    public $params;

    /**
     * 校验规则
     *
     * @var array
     */
    public $rules;

    /**
     * 提示信息
     *
     * @var array
     */
    public $msgs;


    public function __construct($validatorObj)
    {
        $this->params = $validatorObj->params;
        $this->rules = $validatorObj->rules;
        $this->msgs = $validatorObj->msgs;
    }

    /**
     * 设置错误信息
     *
     * @param string $columnName
     * @param string $errorTag
     * @return void
     */
    public function setError($columnName, $errorTag)
    {
        if (isset($this->msgs[$columnName]) && !empty($this->msgs[$columnName][$errorTag])) {
            $this->error[] = $this->msgs[$columnName][$errorTag];
        } else {
            $error = '';
            switch ($errorTag) {
                case 'required':
                    $error = $columnName.'字段值不能为空';
                    break;
                case 'max':
                    $error = $columnName.'数字大小超出限制';
                    break;
                case 'min':
                    $error = $columnName.'数字不得超出最小限制';
                    break;
                case 'format':
                    $error = $columnName.'必须为日期时间格式';
                    break;
                default:
                    break;
            }
            $this->error[] = $error;
        }
        return;
    }

    /**
     * 必需验证
     * @return void
     */
    protected function required($columnName)
    {
        if ( isset($this->rules[$columnName]['required']) && boolval($this->rules[$columnName]['required'])) {
            if(!isset($this->params[$columnName]) || $this->params[$columnName]==''){
                // 键值不存在 - 直接抛出必填项缺失
                $this->setError($columnName, 'required');
            }
        }
        return;
    }



    /**
     * 格式校验
     * @return void
     */
    protected function format($columnName)
    {
        if (isset($this->params[$columnName])) {
            if( isset($this->rules[$columnName]['required']) && boolval($this->rules[$columnName]['required'])){
                // 必填项-非空字符串非null会校验格式
                if($this->params[$columnName] != '' && !$this->checkDateTimeFormat($this->params[$columnName])){
                    $this->setError($columnName, 'format');
                }
            }else{
                // 非必填项 - 非空 - 做格式校验
                if($this->params[$columnName] != ''){
                    if(!$this->checkDateTimeFormat($this->params[$columnName])){
                        $this->setError($columnName, 'format');
                    }
                }
            }

        }
        return;
    }

    /**
     * 日期最大值校验
     *
     * @param string $columnName
     * @return void
     */
    protected function max($columnName)
    {
        if (isset($this->params[$columnName]) && strtotime($this->params[$columnName])){
            if(!empty($this->rules[$columnName]['max']) && strtotime($this->rules[$columnName]['max'])){
                if(strtotime($this->params[$columnName]) > strtotime($this->rules[$columnName]['max'])){
                    $this->setError($columnName, 'max');
                }
            }
        }
        return;
    }

    /**
     * 日期最小值校验
     *
     * @param string $columnName
     * @return void
     */
    protected function min($columnName)
    {
        if ( isset($this->params[$columnName]) && strtotime($this->params[$columnName])){
            if(!empty($this->rules[$columnName]['min']) && strtotime($this->rules[$columnName]['min'])){
                if(strtotime($this->params[$columnName]) < strtotime($this->rules[$columnName]['min'])){
                    $this->setError($columnName, 'min');
                }
            }
        }
        return;
    }

    /**
     * 校验日期格式
     *
     * @param string $date
     * @link https://www.runoob.com/w3cnote/date-format-validation-in-php.html
     * @return void
     */
    function checkDateTimeFormat($date)
    {
        $dateTime = date_create($date);
        if (!$dateTime || $date != date_format($dateTime,'Y-m-d H:i:s')) {
            return false;
        }
        return true;
    }

    /**
     * 格式化日期
     *
     * @param string $columnName
     * @return void
     */
    protected function layout($columnName)
    {
        if (isset($this->params[$columnName]) && $this->checkDateTimeFormat($this->params[$columnName])){
            if(!empty($this->rules[$columnName]['layout'])){
                $this->params[$columnName] = date($this->rules[$columnName]['layout'], strtotime($this->params[$columnName]));
            }
        }
        return;
    }



    /**
     * 获取验证后的数据
     *
     * @param string $columnName
     * @return void
     */
    public function getParam($columnName)
    {
        return $this->params[$columnName];
    }

    /**
     * 获取验证出的错误信息
     *
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 设置默认零值
     * @return void
     */
    protected function setDefault($columnName)
    {
        if (isset($this->params[$columnName])) {
            if(in_array('default',array_keys($this->rules[$columnName]))){
                if ($this->params[$columnName] == "") {
                    $this->params[$columnName] = $this->rules[$columnName]['default'];
                }
            }
        }
        return;
    }

    /**
     * 校验
     * 1.必填项 - 传递null或者空字符串都会告警不能为空 | 不告警格式错误
     * 2.格式化 - 必须为正确的时间格式 - 才会执行layout格式化
     * 3.格式校验 - 必填项为非字符串非null校验格式 | 非必填项非空字符串非null校验格式
     * 4.默认值 - 必填项不做处理|非必填项为null或空字符串时候置为默认零值 
     * 
     *
     * @param string $columnName
     * @return void
     */
    public function validate($columnName)
    {
        // 1 必填校验
        $this->required($columnName);
        // 2 格式校验
        $this->format($columnName);
        // 3 默认零值
        $this->setDefault($columnName);
        // 4 最大数值校验
        $this->max($columnName);
        // 5 最小数值校验
        $this->min($columnName);
        // 6 格式化
        $this->layout($columnName);
        return;
    }
}
