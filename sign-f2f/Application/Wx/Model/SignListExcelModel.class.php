<?php
namespace Wx\Model;

class SignListExcelModel
{
    //10进制转26进制
    public function num2alpha($num)
    {
        $alpha = 'ZABCDEFGHIJKLMNOPQRSTUVWXY';
        $result = '';
        while($num) {
            $temp  = $num % 26;
            $num = ($temp == 0) ? (intval($num / 26) - 1) : intval($num / 26);
            $result  = $alpha[$temp].$result;
        }
        return $result;
    }

    //总计
    public function sum($signdata)
    {
	$data = $signdata['list'];

        //画Excel表格
        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);

        //默认样式
        $objPHPExcel->getActiveSheet()->setTitle('签到详细列表');
        $objPHPExcel->getDefaultStyle()->getFont()->setName('宋体');
        $objPHPExcel->getDefaultStyle()->getFont()->setSize(13);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setWrapText(true);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //设置活跃active
        $activeSheet = $objPHPExcel->getActiveSheet();

        //总长度及高度
        $excelWidth  = 5;
        $excelHeight = count($data) + 2;
        $allIndex = 'A1:' . $this->num2alpha($excelWidth) . ($excelHeight);

        //设置边框
        $activeSheet->getStyle($allIndex)->applyFromArray(array(
            'borders' => array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN
                ),
            ),
        ));

        //设置表头背景
        $excelIndex = 'A1:' . $this->num2alpha($excelWidth) . 2;
        $activeSheet->getStyle($excelIndex )->getFill()->setFillType(
            \PHPExcel_Style_Fill::FILL_SOLID
        )->getStartColor()->setARGB('00C5CFCA');
        //固定表头
        $activeSheet->freezePane('A3');

        //第一行
	$title = '签到名称：'.$signdata['launchname'].'  发起时间：'.$signdata['launchtime'].'  签到码：'.$signdata['signcode'].'  总签到人数：'.$signdata['count'];

        $excelIndex = 'A1';
        $activeSheet->setCellValue('A1', $title);
        $activeSheet->getStyle('A1')->getFont()->setBold(true);
        $activeSheet->getStyle('A1')->getFont()->setSize(13);
        $activeSheet->mergeCells('A1:' . $this->num2alpha($excelWidth) . '1');
        $activeSheet->getRowDimension('1')->setRowHeight(40);

        //第二行
        $col = array('序号', '姓名', '学号', '签到时间','备注');
        for ($i = 0; $i < count($col); $i++) {
            //填写数据
            $excelIndex = $this->num2alpha(($i + 1)) . '2';
            $activeSheet->setCellValue($excelIndex, $col[$i]);

            //设置宽度
            if (in_array($i, array(1, 2, 3, 4))) {
                $activeSheet->getColumnDimension($this->num2alpha(($i + 1)))->setWidth(20);
            } else {
                $activeSheet->getColumnDimension($this->num2alpha(($i + 1)))->setWidth(10);
            }
        }
        //设置高度
        $activeSheet->getRowDimension('2')->setRowHeight(20);

        //输出所有详情
	//$signdata = $data['list'];

        for($i = 0; $i < count($data); $i++) {
            $col = $data[$i];
            $col = array(
                ($i + 1), $col['username'], $col['num'], $col['signtime']
            );
            $rowIndex = $i + 3;
            for ($j = 0; $j < count($col); $j++) {
                $excelIndex = $this->num2alpha(($j + 1)) . $rowIndex;
                $activeSheet->setCellValue($excelIndex, $col[$j]);
            }
            //单双行背景
            if (($i % 2) == 0) {
                $excelIndex = 'A' . $rowIndex . ':' . $this->num2alpha($excelWidth) . $rowIndex;
                $activeSheet->getStyle($excelIndex)->getFill()->setFillType(
                    \PHPExcel_Style_Fill::FILL_SOLID
                )->getStartColor()->setARGB('00eeeeee');
            }
        }

        //输出下载
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename={$signdata['launchname']}.xls");
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

}
