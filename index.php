<?php

/*
	- Iedere deelnemer kiest 3 workshops
	- 6 zalen
	- 14 a 15 trainingen
	- 4 timeslots per zaal
*/

/*
	1) Timeslots
		2) Workshops (Max 6)
			3) Deelnemers 

	$a_Indeling[1][0] = array('A1','A2','A3');
	$a_Conflicts[1][0] = array('A1','A2','A3');
	
*/



class Planner {
	
	private $cfg = array();
	
	private $a_Indeling = array();
	private $a_Conflicts = array();	
	private $a_Workshops = array();
	private $a_Deelnemers = array();
	private $a_DeelnemersFromStart = array();	
	private $a_DeelnemersAssigned = array();
	private $a_WorkshopsNotPlaced = array();
	private $a_Rest = array();
		
	public function init(){

		// set config
		$this->cfg['timeslots_naming'][1] = '11:00 - 11:55';
		$this->cfg['timeslots_naming'][2] = '12:00 - 12:55';
		$this->cfg['timeslots_naming'][3] = '13:00 - 13:55';
		$this->cfg['timeslots_naming'][4] = '14:00 - 14:55';
	
		$this->cfg['zalen'] = range('1', '6');
		$this->cfg['timeslots'] = range('1', '4');
	
		$this->cfg['workshops'][] = 'Qredits';
		$this->cfg['workshops'][] = 'Spotcap';
		$this->cfg['workshops'][] = 'Alfa Commercial Finance';
		$this->cfg['workshops'][] = 'Collin Crowdfund';
		$this->cfg['workshops'][] = 'FundIQ';
		$this->cfg['workshops'][] = 'Matchinvest';
		$this->cfg['workshops'][] = 'NEOS';
		$this->cfg['workshops'][] = 'NL Credit Services';
		$this->cfg['workshops'][] = 'Finker';
		$this->cfg['workshops'][] = 'DCMF';
		$this->cfg['workshops'][] = 'Funding Circle';
		$this->cfg['workshops'][] = 'Pecu Nova';
		$this->cfg['workshops'][] = 'NPEX';
		$this->cfg['workshops'][] = 'Liesker';
	
	
		// Generate deelnemers
		$letters = range('A', 'Z');
		$cijfers = range('1', '3');
		foreach ($letters as $letter) {
			foreach ($cijfers as $cijfer) {
				
				// pick random workshops for each deelnemer
				$a_Workshops = array();
				for($i=1; $i<=3; $i++){
					do {
						$index = rand(0, count($this->cfg['workshops'])-1);			
					} while(in_array($this->cfg['workshops'][$index], $a_Workshops));		
					$a_Workshops[] = $this->cfg['workshops'][$index];
				}
				
				$this->a_Deelnemers[] = array('naam' => $letter.$cijfer, 'workshops' => $a_Workshops);
			}
		}		
		
		// Assign the workshops
		foreach($this->cfg['workshops'] as $workshop)
			$this->a_Workshops[$workshop] = 0;
			
		// set the indeling keys
		foreach($this->cfg['timeslots'] as $timeslot)
			$this->a_Indeling[$timeslot] = array();
			
		$this->a_DeelnemersFromStart = $this->a_Deelnemers; // for controle.
			
		$this->sortWorkshopsOnPopularity();
		$this->run();
	}
	

	/**
	 * @name run
	 */
	private function run(){
		// Loop over alle workshops en plaats ze achter elkaar
		foreach($this->a_Workshops as $workshop => $count_deelnemers)
			$this->assignWorkshopToTimeslot($workshop);

		// probeer de workshop aan ieder timeslot te koppelen en dan kijken welke de minste problemen oplevert				
		foreach($this->a_WorkshopsNotPlaced as $workshop){
			for($i=0; $i<count($this->cfg['timeslots']); $i++){
				$assignedToTimeslot = $this->assignWorkshopToTimeslot($workshop, $i, true);	// force to this timeslot (if we have an empty spot left)
				
				// Wat levert dat aan conflicten op voor deze workshop  
				if($assignedToTimeslot){
					if($assignedToTimeslot == $i+1)
						$a_ConflictsForThisTimeslot[$assignedToTimeslot] = $this->getNumberOfConflictsForWorkshopInTimeslot($workshop, $assignedToTimeslot);
				}
				// deassign meteen weer
				$this->deassignWorkshopFromTimeslot($workshop, $assignedToTimeslot);
			}
			
			asort($a_ConflictsForThisTimeslot);
			
			// The best timeslot is: 
			foreach($a_ConflictsForThisTimeslot as $timeslot => $errors){
				$bestTimeslot = $timeslot;
				break;
			}
			
			$this->assignWorkshopToTimeslot($workshop, $bestTimeslot-1, true); // assign to that timeslot
			
//			print "Best timeslot for <strong>$workshop</strong> is <strong>$bestTimeslot</strong><br />";
		}		
			
		//print "<strong>Conflicts</strong><br />";			
		//print_r($this->a_Conflicts);
		
				
		// We gaan nu alle conflicten bij langs. Eerst de workshop met de meeste conflicten.
		// Kunnen we die workshop nog aan een ander timeslot koppelen en dan de mensen die dubbele hebben in dat timeslot mee laten doen aan die workshop.
		for($q=0; $q<10; $q++){
			foreach($this->a_Conflicts as $timeslot => $a_Workshops){
				
				// Loop over alle workshops in dit timeslot met conflicten.
				// De workshop met de meeste conflicten staat bovenaan. Die behandelen we dus eerst.
				foreach($a_Workshops as $workshop => $a_Data){
					$a_DeelnemersDubbel = array();
					$a_DeelnemersThisWorkshop = $this->getDeelnemersForWorkshopAndTimeslot($workshop, $timeslot);
					$a_DeelnemersDubbelThisWorkshop = array();
					
					foreach($a_Data['workshops'] as $workshopMetDubbele => $a_CountDubbele){
								
						//print "1: $workshopMetDubbele ($a_CountDubbele)<br />";
					
						$a_DeelnemersThisWorkshop = $this->getDeelnemersForWorkshopAndTimeslot($workshop, $timeslot);
						$a_DeelnemersDubbel[$timeslot] = $this->getDeelnemersForWorkshopAndTimeslot($workshopMetDubbele, $timeslot);
						foreach($a_DeelnemersDubbel[$timeslot] as $deelnemer)
							if(in_array($deelnemer, $a_DeelnemersThisWorkshop))
								$a_DeelnemersDubbelThisWorkshop[$timeslot][] = $deelnemer;
					}
					//print "<strong>Dit zijn de dubbelen voor $workshop in ts $timeslot</strong><br />";
					if(isset($a_DeelnemersDubbelThisWorkshop[$timeslot]) && is_array($a_DeelnemersDubbelThisWorkshop[$timeslot])){
					//	print_r($a_DeelnemersDubbelThisWorkshop[$timeslot]);
					
						// Probeer de workshop naar een ander timeslot te kopieren en dan die dubbelen in dat timeslot mee te laten doen
						if($bestTimeslot = $this->assignWorkshopToBestTimeslot($workshop, $a_DeelnemersDubbelThisWorkshop[$timeslot])){
							// Deassign deelnemers from the timeslot. Ze gaan de cursus ergens anders volgen.
							$this->deassignDeelnemersFromWorkshopInTimeslot($workshop, $timeslot, $a_DeelnemersDubbelThisWorkshop[$timeslot]);
							
							if(count($this->a_Indeling[$bestTimeslot]) > count($this->cfg['zalen'])){
								//print "Timeslot $bestTimeslot is overgeboekt<br />";
								
								$this->assignWorkshopToRest($workshop, $bestTimeslot);																
							}
						}					
					}
				}
		
				break;
			}
			$this->renderConflicts();
		}
			
			
		//$this->renderConflicts(); // Conflicts zijn gesort van hoog naar laag aantal
		$this->renderDeelnemers();
		$this->renderIndeling();
			
//		print_r($this->a_Deelnemers);
//		print "<strong>Conflicts</strong><br />";			
//		print_r($this->a_Conflicts);
		
//		print "<strong>Indeling</strong><br />";			
//		print_r($this->a_Indeling);

//		print "<strong>Deelnemers</strong><br />";			
//		print_r($this->a_Deelnemers);
		

	}
	
	private function assignWorkshopToRest($workshop, $timeslot){
		//print "Assigning <strong>$workshop</strong> to rest. Removing from <strong>$timeslot</strong><br />";
		$this->a_Rest[$workshop][] = $this->a_Indeling[$timeslot][$workshop];
		unset($this->a_Indeling[$timeslot][$workshop]);
	}
	
	private function deassignDeelnemersFromWorkshopInTimeslot($workshop, $timeslot, $a_Deelnemers){
		$a_DeelnemersDieOverblijven = array();
		foreach($this->a_Indeling[$timeslot][$workshop] as $deelnemer)
			if(!in_array($deelnemer, $a_Deelnemers))
				$a_DeelnemersDieOverblijven[] = $deelnemer;
				
		$this->a_Indeling[$timeslot][$workshop] = $a_DeelnemersDieOverblijven;
	}
	
	private function assignWorkshopToBestTimeslot($workshop, $a_Deelnemers = false){
		for($i=0; $i<count($this->cfg['timeslots']); $i++){
			$assignedToTimeslot = $this->assignWorkshopToTimeslot($workshop, $i, true, $a_Deelnemers, true);	// force to this timeslot (if we have an empty spot left)
			
			// Wat levert dat aan conflicten op voor deze workshop  
			if($assignedToTimeslot){
				if($assignedToTimeslot == $i+1)
					$a_ConflictsForThisTimeslot[$assignedToTimeslot] = $this->getNumberOfConflictsForWorkshopInTimeslot($workshop, $assignedToTimeslot);
			}
			// deassign meteen weer
			$this->deassignWorkshopFromTimeslot($workshop, $assignedToTimeslot);
		}
		if($a_ConflictsForThisTimeslot)
			asort($a_ConflictsForThisTimeslot);
		
		// The best timeslot is: 
		foreach($a_ConflictsForThisTimeslot as $timeslot => $errors){
			$bestTimeslot = $timeslot;
			break;
		}
		
		$this->assignWorkshopToTimeslot($workshop, $bestTimeslot-1, true, $a_Deelnemers); // assign to that timeslot
		
		//print "Best timeslot for <strong>$workshop</strong> is <strong>$bestTimeslot</strong><br />";
		return $bestTimeslot;		
	}

	private function getDeelnemersForWorkshopAndTimeslot($workshop, $timeslot){
		return isset($this->a_Indeling[$timeslot][$workshop]) ? $this->a_Indeling[$timeslot][$workshop] : false;
	}

	private function sortWorkshopsOnPopularity(){
		foreach($this->a_Deelnemers as $a_Deelnemer)
			foreach($a_Deelnemer['workshops'] as $workshop)
				$this->a_Workshops[$workshop]++;
				
		arsort($this->a_Workshops);
	}
	
	private function getNumberOfConflictsForWorkshopInTimeslot($workshop, $timeslot){
		return isset($this->a_Conflicts[$timeslot][$workshop]) ? $this->a_Conflicts[$timeslot][$workshop] : 0;
	}	
	
	private function deassignWorkshopFromTimeslot($workshop, $timeslot){
		unset($this->a_Indeling[$timeslot][$workshop]);
	}
	
	private function assignWorkshopToTimeslot($workshop, $timeslot = 0, $force = false, $a_DeelnemersToAssign = false, $print = false){
		$timeslot++;
		
//		if($print)
//			print 'hier: '.$workshop.' '.$timeslot.'<br />';
		
		
		
		if($timeslot > count($this->cfg['timeslots'])){
			$this->a_WorkshopsNotPlaced[] = $workshop;
		//	if($print)
		//		print 'We kunnen <strong>'.$workshop.'</strong> niet plaatsen<br />';
			return false;						
		}
		
		if(isset($this->a_Indeling[$timeslot][$workshop])){
//			if($print)
//				print 'hier: '.$workshop.' '.$timeslot.' error 1<br />';
			$this->assignWorkshopToTimeslot($workshop, $timeslot);
			return;
		}
			
		if(count($this->a_Indeling[$timeslot]) > count($this->cfg['zalen'])){
		//	if($print)
		//		print 'hier: '.$workshop.' '.$timeslot.' error 2<br />';
			$this->assignWorkshopToTimeslot($workshop, $timeslot);
			return;
		}
				
//		if($force)
//			print 'We gaan <strong>'.$workshop.'</strong> plaatsen aan <strong>'.$timeslot.'</strong><br />';
				
		// Loop over de deelnemers die deze workshop hebben en assign ze hieraan: $this->a_Indeling[$timeslot][$workshop]
		if($a_DeelnemersToAssign){
			foreach($a_DeelnemersToAssign as $deelnemer)
				$this->a_Indeling[$timeslot][$workshop][] = $deelnemer;				
		} else 
			foreach($this->a_Deelnemers as $a_Deelnemer)
				foreach($a_Deelnemer['workshops'] as $deelnemer_workshop)
					if($workshop == $deelnemer_workshop)
						$this->a_Indeling[$timeslot][$workshop][] = $a_Deelnemer['naam'];
			
					
//		if($print)
//			print 'We hebben net de indeling voor <strong>'.$timeslot.' / '.$workshop.'</strong> bepaald<br />';			
					
		$this->renderConflicts();
		if(!$force)
			if(isset($this->a_Conflicts[$timeslot][$workshop])){
//			print "Conflict: proberen <strong>$workshop</strong> aan <strong>".$timeslot." (plus 1)</strong> te koppelen<br />";
				unset($this->a_Indeling[$timeslot][$workshop]);
				$this->assignWorkshopToTimeslot($workshop, $timeslot);
			} else {
//			print "Succes: <strong>$workshop</strong> aan <strong>".$timeslot." </strong> gekoppeld<br />";			
			}
		
		return $timeslot;
	}
	
	
	private function renderConflicts(){
		$this->a_Conflicts = array();
		foreach($this->a_Indeling as $timeslot => $a_Workshops)
			foreach($a_Workshops as $workshop => $_a_Deelnemers){
				$a_DeelnemersIngedeeld = array();
						
				foreach($this->a_Indeling[$timeslot] as $workshop_timeslot => $a_Deelnemers){
					if($workshop == $workshop_timeslot)
						continue;
					
					foreach($a_Deelnemers as $deelnemer_workshop)
						if(in_array($deelnemer_workshop, $this->a_Indeling[$timeslot][$workshop])){
							$this->a_Conflicts[$timeslot][$workshop]['count'] = isset($this->a_Conflicts[$timeslot][$workshop]['count']) ? $this->a_Conflicts[$timeslot][$workshop]['count']+1 : 1;
							$this->a_Conflicts[$timeslot][$workshop]['workshops'][$workshop_timeslot] = isset($this->a_Conflicts[$timeslot][$workshop]['workshops'][$workshop_timeslot]) ? $this->a_Conflicts[$timeslot][$workshop]['workshops'][$workshop_timeslot]+1 : 1; 
//							print 'Conclict voor <strong>'.$workshop.'</strong>: <strong>'.$deelnemer_workshop.'</strong> doet ook mee met <strong>'.$workshop_timeslot.'</strong> in timeslot <strong>'.$timeslot.'</strong><br />';
						}
				}				
			}
						
		// sort the conflicts from high to low
		foreach($this->a_Conflicts as $timeslot => $a_Workshops)
			uasort($this->a_Conflicts[$timeslot], function($a, $b) {
				return $b['count'] - $a['count'];
			});
			
		foreach($this->a_Conflicts as $timeslot => $a_Workshops)			
			foreach($a_Workshops as $workshop => $a_Data){				
				arsort($a_Data['workshops']);
				$this->a_Conflicts[$timeslot][$workshop] = $a_Data;
			}
	}
	
	private function renderDeelnemers(){
		foreach($this->a_Indeling as $timeslot => $a_Workshops)
			foreach($a_Workshops as $workshop => $a_Deelnemers)
				foreach($a_Deelnemers as $naam)
					$this->a_DeelnemersAssigned[$naam][$timeslot][] = $workshop;				
	}
	
	private function renderIndeling(){
		foreach($this->a_Indeling as $timeslot => $a_Workshops)
			for($i=count($a_Workshops); $i<count($this->cfg['zalen']); $i++)
				$this->a_Indeling[$timeslot][$i+1] = false;		
	}
	
	public function printHeader(){
		print '<strong>Beschikbare workshops</strong><br />';
		print join(' - ',$this->cfg['workshops']);
		print '<br />~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~<br /><br />';
		
		print '<strong>Aantal deelnemers</strong><br />';
		print count($this->a_DeelnemersFromStart);
		print '<br />~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~<br /><br />';
		
		
		print '<strong>Deelnemer / Gekozen Workshops</strong><br />';
		foreach($this->a_Deelnemers as $deelnemer){
			print $deelnemer['naam'].' / ';
			print ' '.join(' - ', $deelnemer['workshops']);
			print '<br />';
		}
		
		print '~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~<br /><br />';
		
		$a_DeelnemersOK = array();
		foreach($this->a_DeelnemersFromStart as $a_Deelnemer){
			foreach($a_Deelnemer['workshops'] as $workshop_deelnemers){
				foreach($this->cfg['timeslots'] as $timeslot)
					if(isset($this->a_Indeling[$timeslot][$workshop_deelnemers]) && in_array($a_Deelnemer['naam'], $this->a_Indeling[$timeslot][$workshop_deelnemers])){
						$a_DeelnemersOK[$a_Deelnemer['naam']][] = $workshop_deelnemers;
					}
			}
		}
		
		print "<strong>De volgende deelnemers hebben we niet voor alle cursussen kunnen indelen.</strong><br />";
		foreach($a_DeelnemersOK as $deelnemer => $a_Workshops)
			if(count($a_Workshops) < 3)
				print "$deelnemer, ";		
		print '<br />~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~<br /><br />';
		print "<strong>Nog te plaatsen cursussen met deelnemers</strong><br />";
		foreach($this->a_Rest as $workshop => $a_Data){
			print $workshop.' // ';
			print join(', ', $a_Data[0]).'<br />';
		}
	}
	
	public function getDeelnemers(){
		return $this->a_DeelnemersAssigned;
	}
	
	public function getIndeling(){		
		return $this->a_Indeling;
	}
	
	public function getWorkshops(){
		return $this->a_Workshops;
	}
	
	public function getCfg(){
		return $this->cfg;
	}
	
		
	private function getWorkshopsForTimeslot($timeslot){}
	private function getWorkshopForDeelnemerInTimeslot($deelnemer, $timeslot){}	
	private function getTimeslotForDeelnemerForWorkshop($deelnemer, $workshop){}
	
	private function getWorkshopsForDeelnemer($deelnemer){}
	private function getDeelnemersForWorkshopForTimeslot($workshop, $timeslot){}
}

	
print '<pre>';
$planner = new Planner();
$planner->init();
$planner->printHeader();

$a_Deelnemers = $planner->getDeelnemers();
$a_Indeling = $planner->getIndeling();
$a_Workshops = $planner->getWorkshops();
$cfg = $planner->getCfg();	
	
?>

<html>
	<head>
		<style type='text/css'>
			table tr td
				{
					background-color: #e1e1e1;
				}
			td
				{
					text-align: center;
					padding: 2px;
				}
			table tr td.start
				{
					border-left: 2px solid grey;
				}
			.left
				{
					text-align: left;
				}
		</style>
	</head>
	<body>
		<table>
			<tr>
				<td></td>
				<?php for($i=1; $i<=count($cfg['timeslots']); $i++): ?>
				<td colspan='6' class='start'><strong>timeslot / zaal / workshop</strong></td>
				<?php endfor; ?>
			</tr>
			<tr>
				<td></td>
				<?php foreach($a_Indeling as $timeslot => $a_Workshops): ?>
				<td colspan='6' class='start'><?=$cfg['timeslots_naming'][$timeslot]?></td>
				<?php endforeach; ?>
			</tr>
			<tr>
				<td></td>
				<?php foreach($a_Indeling as $timeslot => $a_Workshops): ?>
					<?php for($i=1; $i<=count($cfg['zalen']); $i++): ?>
						<td <?php if($i==1): ?>class='start'<?php endif; ?>><?=$i?></td>
					<?php endfor; ?>
				<?php endforeach; ?>
			</tr>
			<tr>
				<td></td>
				<?php foreach($a_Indeling as $timeslot => $a_Workshops): $isset = false; ?>
					<?php foreach($a_Workshops as $workshop => $deelnemers): ?>
						<?php if(is_int($workshop)):?>
						<td <?php if(!$isset): ?> class='start'<?php endif; ?>>&nbsp;</td>
						<?php else: ?>
						<td <?php if(!$isset): ?> class='start'<?php endif; ?>><?=$workshop?></td>
						<?php endif;?>
						<?php $isset = true; ?>
					<?php endforeach; ?>
				<?php endforeach; ?>
			</tr>
							
			<?php $cycle = -1; foreach($a_Deelnemers as $naam => $a_Timeslots): ?>
			<tr>
				<td class='left'><?=$naam?></td>
				<?php foreach($a_Indeling as $timeslot => $a_Workshops): ?>
					<?php foreach($a_Workshops as $workshop => $deelnemer): ?>
						<?php $cycle++; ?>
						<?php if(isset($a_Timeslots[$timeslot]) && in_array($workshop, $a_Timeslots[$timeslot])): ?>
							<td <?php if($cycle % 6 == 0): ?> class='start'<?php endif; ?>>x</td>
						<?php else: ?>
							<td <?php if($cycle % 6 == 0): ?> class='start'<?php endif; ?>>&nbsp;</td>
						<?php endif; ?>						
					<?php endforeach; ?>
				<?php endforeach; ?>				
			</tr>	
			<?php endforeach; ?>				
			
		</table>
	</body>
</html>