<?php

namespace suffi\naumenRest;

/**
 * Запросник для кейсов
 * Class CallCases
 * @package suffi\naumenRest
 */
class CallCases extends Request
{
    /** Статус "Новый" */
    const statusNew = 'new';

    /** Статус "Недозвон" */
    const statusUnavailable = 'unavailable';

    /** Статус "Отказ" */
    const statusRefused = 'refused';

    /** Статус "Дозвон" */
    const statusAvailable = 'available';

    /** Статус "Отложен" */
    const statusAdjourned = 'adjourned';

    /** Статус "Выполнен" */
    const statusFinished = 'finished';

    /** Статус "Выполнен/не реализован" */
    const statusNRcompleted = 'completed_nr';

    /** Статус "Выполнен/реализован" */
    const statusRcompleted = 'completed_r';

    /**
     * Дополнительный урл модуля
     * @var string
     */
    protected $url = '/callcases/';

    /**
     * Uiid проекта
     * @var string
     */
    private $projectUuid = '';

    /**
     * @param string $projectUuid
     */
    public function setProjectUuid($projectUuid)
    {
        $this->projectUuid = $projectUuid;
    }

    /**
     * @return string
     */
    public function getProjectUuid()
    {
        return $this->projectUuid;
    }

    /**
     * Получение кейса
     * @param string $id Uiid кейса
     * @return mixed
     */
    public function get($id)
    {
        return $this->_get('/callcases/' . $id);
    }

    /**
     * Создание кейса
     * @param $data
     * @return bool|mixed
     * @throws Exception
     */
    public function create($data)
    {
        $uiid = $this->_post(['callcase' => $data], '/callcases/?project=' . $this->projectUuid, true);
        if ($this->getErrorCode()) {
            return false;
        } else {
            return $uiid;
        }
    }

    /**
     * Обновление кейса
     * @param $data
     * @return bool
     * @throws Exception
     */
    public function update($data)
    {
        $this->_put(['callcase' => $data], '/callcases/');
        if ($this->getErrorCode()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Удаление кейса
     * @param string $id Uiid кейса
     * @return mixed
     */
    public function delete($id)
    {
        $this->_delete([], '/callcases/' . $id);
        if ($this->getErrorCode()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Состояние кейса
     * @param string $id Uiid кейса
     * @return mixed
     */
    public function getState($id)
    {
        return $this->_get('/callcases/' . $id . '/get-state', false);
    }

    /**
     * Изменение состояния кейса
     * @param string $id Uiid кейса
     * @param array $params
     *      state - Состояние
     *      operator - Оператор
     *      date - Дата
     * @return mixed
     * @throws Exception
     */
    public function setState($id, array $params = [])
    {
        if (!isset($params['date'])) {
            $params['date'] = date('Y-m-d\TH:i:s');
        }

        $this->_post($params, '/callcases/' . $id . '/set-state', false, false);
        if ($this->getErrorCode()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Получение списка
     * @param array $params Дополнительные параметры
     *      state - Состояние
     *      operator - Оператор
     *      modifiedAfter, modifiedBefore - Фильтр по времени модификации
     *      page - Cтраница
     * @param bool $full Флаг полной выборки. Если включен, делает запросы всех страниц списка
     * @return array|false
     */
    public function getList(array $params = [], $full = false)
    {
        $getParams = '/callcases/?project=' . $this->projectUuid;
        if ($params) {
            foreach ($params as $key => $value) {
                if ($full && $key == 'page') {
                    continue;
                }
                $getParams .= '&' . $key . '=' . $value;
            }
        }

        if ($full) {
            $data = [];
            $i = 1;
            do{
                $pageData = $this->_get($getParams . '&page=' . $i);
                $i++;
                if (isset($pageData['callcase'])) {
                    foreach ($pageData['callcase'] as $value) {
                        $data['callcase'][] = $value;
                    }
                }
            }while(!$this->getError());

        } else {
            $data = $this->_get($getParams);
        }

        if (!isset($data['count']) && isset($data['callcase'])) {
            $data['count'] = count($data['callcase']);
        }

        if (isset($data['count']) && $data['count'] == 1 && isset($data['callcase']) && !isset($data['callcase'][0])) {
            $data['callcase'] = [0 => $data['callcase']];
        }

        return $data;

    }

    /**
     * Создание по списку
     * @param array $cases
     * @return array
     * @throws Exception
     */
    public function createList(array $cases) {
        return $this->_post(['callcases' => $cases], '/projects/' . $this->projectUuid . '/callcases-batch/', false);
    }

    /**
     * Обновление по списку
     * @param array $cases
     * @return array
     * @throws Exception
     */
    public function updateList(array $cases) {
        return $this->_put(['callcases' => $cases], '/projects/' . $this->projectUuid . '/callcases-batch/');
    }

    /**
     * Удаление по списку
     * @param array $cases
     * @return bool
     */
    public function deleteList(array $cases)
    {
        /**
         * #fixme делать разные действия по $this->format тут нельзя, но с json команда работает сильно по-другому
         */
        $data = [];
        foreach ($cases as $case) {
            if (isset($case['uuid'])) {
                $data[($this->format == 'xml') ? 'objectLinks' : 'uuid'][] = ['uuid' => $case['uuid']];
            } else {
                if (isset($case['id'])) {
                    $data[($this->format == 'xml') ? 'objectLinks' : 'id'][] = ['id' => $case['id']];
                }
            }
        }
        if ($this->format == 'xml') {
            $data['root'] = 'objectLinks';
        }

        $this->_delete($data, '/projects/' . $this->projectUuid . '/callcases-batch/');

        if ($this->getErrorCode()) {
            return false;
        } else {
            return true;
        }

    }

    /**
     * Получение кейса по внешнему идентификатору
     * @param string $id Id кейса
     * @return mixed
     */
    public function getByExtId($id)
    {
        return $this->_get('/projects/' . $this->projectUuid . '/callcases/' . $id);
    }

    /**
     * Создание кейса с внешним идентификатором
     * @param $data
     * @return string|false
     * @throws Exception
     */
    public function createWithId($data)
    {
        if (!isset($data['id'])) {
            throw new Exception('id not exist');
        }
        $uiid = $this->_post(['callcase' => $data], '/projects/' . $this->projectUuid . '/callcases/', true);

        if ($this->getErrorCode()) {
            return false;
        } else {
            return $uiid;
        }
    }

    /**
     * Обновление кейса по внешнему идентификатору
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function updateByExtId($data)
    {
        if (!isset($data['id'])) {
            throw new Exception('id not exist');
        }
        $this->_put(['callcase' => $data], '/projects/' . $this->projectUuid . '/callcases/');
        if ($this->getErrorCode()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Удаление кейса по внешнему идентификатору
     * @param string $id Uiid кейса
     * @return mixed
     */
    public function deleteByExtId($id)
    {
        $this->_delete([], '/projects/' . $this->projectUuid . '/callcases/' . $id);
        if ($this->getErrorCode()) {
            return false;
        } else {
            return true;
        }
    }

}