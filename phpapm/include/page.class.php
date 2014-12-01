<?php

/**
 * 分页类
 * Class page
 */
class page
{

    var $limit_1 = 0;
    var $limit_2 = 0;
    var $num_1 = "select t_page_1.* from (select rownum rn ,t_page_0.* from (\n";
    var $num_3 = "\n) t_page_0 where rownum <= :num_3) t_page_1 where rn >:num_1 ";

    function page($total = 0, $everpage = 10, $query = array())
    {
        $this->total = $this->totalItems = $total;
        $this->everpage = $everpage;
        $this->pages = max(1, abs(ceil(($total / $everpage))));
        $this->currentPage = max((int)$_GET['pageID'], 1); //2
        $num = ceil(3 / 2);
        $this->max = MIN(MAX($this->currentPage + $num, 3), $this->pages);
        $this->min = MAX(MIN($this->currentPage - $num, $this->pages - 3), 1);

        $this->limit_1 = ($this->currentPage - 1) * $this->everpage;
        $this->limit_2 = $this->everpage;
        $this->limit_3 = $this->currentPage * $this->everpage;

        $this->num_1 = '';
        $this->num_3 = ' LIMIT ' . $this->limit_1 . ',' . $this->limit_2;
    }

    function show($tp = 'm/page.standard.html')
    {
        include $tp;
    }
}

?>