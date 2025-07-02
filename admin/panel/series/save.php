<?php
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');

function brandTable(int $b):string{
  return match($b){1=>'tools_sgs',2=>'tools_maykestag',3=>'tools_schneider',default=>'tools_generico'};
}
function matTable(string $t):string{ return 'toolsmaterial_'.substr($t,6); }

if($_SERVER['REQUEST_METHOD']!=='POST'){echo json_encode(['success'=>false,'error'=>'Método no permitido']);exit;}
$seriesId=(int)($_POST['series_id']??0);
if(!$seriesId){echo json_encode(['success'=>false,'error'=>'Serie inválida']);exit;}

$serie=$pdo->prepare("SELECT * FROM series WHERE id=?");$serie->execute([$seriesId]);
if(!$serie=$serie->fetch(PDO::FETCH_ASSOC)){echo json_encode(['success'=>false,'error'=>'Serie no existe']);exit;}

$pdo->prepare("UPDATE series SET brand_id=? WHERE id=?")
    ->execute([$_POST['brand_id']??$serie['brand_id'],$seriesId]);

$brandId=(int)($_POST['brand_id']??$serie['brand_id']);
$toolTbl=brandTable($brandId); $matTbl=matTable($toolTbl);

try{
  $pdo->beginTransaction();

  /* herramientas + strategies */
  $cols=['tool_code','diameter_mm','shank_diameter_mm','flute_length_mm','cut_length_mm','full_length_mm',
         'conical_angle','flute_count','radius','coated','rack_angle','helix','material','made_in','image'];
  $mapNew=[];$strategiesToSave=[];
  foreach($_POST['tools']??[] as $tid=>$g){
    $vals=array_map(fn($c)=>$g[$c]??null,$cols);
    if(str_starts_with($tid,'new_')){
      $pdo->prepare("INSERT INTO {$toolTbl}(series_id,".implode(',',$cols).")
                     VALUES (?,".rtrim(str_repeat('?,',count($cols)),',').")")
          ->execute([$seriesId,...$vals]);
      $newId=$pdo->lastInsertId(); $mapNew[$tid]=$newId; $tid=$newId;
    }else{
      $pdo->prepare("UPDATE {$toolTbl} SET ".implode(',',array_map(fn($c)=>"$c=?", $cols))." WHERE tool_id=?")
          ->execute([...$vals,$tid]);
    }
    $strategiesToSave[$tid]=$g['strategies']??[];
  }

  /* toolstrategy: borrar & re-insertar */
  $del=$pdo->prepare("DELETE FROM toolstrategy WHERE tool_table=? AND tool_id=?");
  $ins=$pdo->prepare("INSERT INTO toolstrategy (tool_table,tool_id,strategy_id) VALUES (?,?,?)");
  foreach($strategiesToSave as $tid=>$arr){
    $del->execute([$toolTbl,$tid]);
    foreach($arr as $sid){ if($sid!=='') $ins->execute([$toolTbl,$tid,$sid]); }
  }

  /* parámetros material */
  $pdo->prepare("DELETE pm FROM {$matTbl} pm JOIN {$toolTbl} t USING(tool_id)
                 WHERE t.series_id=?")->execute([$seriesId]);
  foreach($_POST['materials']??[] as $mid=>$d){
    $rating=(int)($d['rating']??0);
    foreach($d['rows']??[] as $tid=>$p){
      if(str_starts_with($tid,'new_')) $tid=$mapNew[$tid]??0;
      if(!$tid) continue;
      $pdo->prepare("INSERT INTO {$matTbl}
        (tool_id,material_id,rating,vc_m_min,fz_min_mm,fz_max_mm,ap_slot_mm,ae_slot_mm)
        VALUES (?,?,?,?,?,?,?,?)")
          ->execute([
            $tid,$mid,$rating,
            $p['vc']??null,$p['fz_min']??null,$p['fz_max']??null,
            $p['ap']??null,$p['ae']??null]);
    }
  }

  $pdo->commit(); echo json_encode(['success'=>true]);
}catch(Exception $e){
  $pdo->rollBack(); echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
