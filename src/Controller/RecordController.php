<?php

namespace App\Controller;

use App\Database;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecordController extends AbstractController {

	private Database $db;

	private HttpClientInterface $httpClient;

	public function __construct( Database $database, HttpClientInterface $httpClient ) {
		$this->db = $database;
		$this->httpClient = $httpClient;
	}

	#[Route( '/{type}/{year}/{num}', name: 'record',
		requirements: [ 'type' => '(birth|marriage|death)', 'year' => '\d{4}', 'num' => '\d+' ]
	)]
	public function record( string $type, string $year, string $num, Request $request ): Response {
		$record = $this->db->getRecord( $type, $year, $num );
		if ( !$record ) {
			throw $this->createNotFoundException();
		}
		return $this->render( 'record.html.twig', [
			'type' => $type,
			'record' => $record,
			'wikidata' => $request->get( 'wikidata' ),
			'wikitree' => $request->get( 'wikitree' ),
		] );
	}

	#[Route( '/{type}/{year}/{num}/save', name: 'record_save',
		requirements: [ 'type' => '(birth|marriage|death)', 'year' => '\d{4}', 'num' => '\d+' ]
	)]
	public function save( string $type, string $year, string $num, Request $request ): Response {
		$record = $this->db->getRecord( $type, $year, $num );
		if ( !$record ) {
			throw $this->createNotFoundException();
		}
		$params = [ 'type' => $type, 'year' => $year, 'num' => $num ];
		$wikidata = $request->get( 'wikidata' );
		if ( $wikidata ) {
			$params['wikidata'] = $wikidata;
			$this->saveWikidata( $record, $type, $wikidata );
		}
		$wikitree = $request->get( 'wikitree' );
		if ( $wikitree ) {
			$params['wikitree'] = $wikitree;
			$this->saveWikiTree( $record, $type, $wikitree );
		}
		return $this->redirectToRoute( 'record', $params );
	}

	private function saveWikiTree( array $record, string $type, string $wikiTree ) {
		$url = "https://www.wikitree.com/wiki/$wikiTree";
		$results = $this->httpClient->request( 'GET', $url );
		if ( $results->getStatusCode() !== 200 ) {
			$this->addFlash( 'warning', "No profile found for $wikiTree" );
			return;
		}
		$html = $results->getContent();
		$refCode = $record['registration_year'] . '/' . $record['registration_number'];
		if ( strpos( $html, $refCode ) === false ) {
			$this->addFlash(
				'warning',
				'<strong>Unable to add WikiTree ID!</strong> '
				. 'The profile does not contain the required code: <code>' . $refCode . '</code>'
			);
		} else {
			$this->addFlash( 'success', 'WikiTree profile ID saved!' );
			$this->db->saveRecordWikiTree( $type . 's', $record['id'], $wikiTree );
		}
	}

	private function saveWikidata( array $record, string $type, string $wikidata ) {
		if ( preg_match( '/Q\d+/', $wikidata ) === 0 ) {
			throw $this->createNotFoundException();
		}
		$url = "https://www.wikidata.org/wiki/Special:EntityData/$wikidata.json";
		$results = $this->httpClient->request( 'GET', $url );
		if ( $results->getStatusCode() !== 200 ) {
			$this->addFlash( 'danger', "No data found for Wikidata item $wikidata" );
			return;
		}
		$json = $results->getContent();
		if ( !$json ) {
			$this->addFlash( 'danger', "No data found for Wikidata item $wikidata" );
			return;
		}
		$data = json_decode( $json, true );
		$entity = reset( $data['entities'] );
		if ( !$entity ) {
			return;
		}

		if ( $type === 'birth' ) {
			$prop = 'P569';
		} elseif ( $type === 'death' ) {
			$prop = 'P570';
		} else {
			throw $this->createNotFoundException();
		}

		if ( $this->checkRef( $entity['claims'][$prop] ?? null ) ) {
			$this->addFlash( 'success', 'Wikidata ID saved!' );
			$this->db->saveRecordWikidata( $type . 's', $record['id'], $entity['id'] );
		} else {
			$url = 'https://quickstatements.toolforge.org/#/v1='
				. urlencode( $this->getQuickStatements( $entity, $type, $record ) );
			$msg = '<p>No suitable reference found on Wikidata item'
				. ' <a href="https://www.wikidata.org/wiki/' . $wikidata . '"'
				. ' target="_blank" class="alert-link" title="Opens in new tab">' . $wikidata . '</a>.</p>'
				. '<p>You can use <a href="' . $url . '" target="_blank" class="alert-link"'
				. ' title="Opens in new tab">QuickStatements</a>'
				. ' to add a reference.</p>';
			$this->addFlash( 'warning', $msg );
		}
	}

	private function getQuickStatements( array $entity, string $type, array $record ): string {
		if ( $type === 'birth' ) {
			$prop = 'P569';
			$value = '+' . $record['year_of_birth'] . '-00-00T00:00:00Z/9';
		} elseif ( $type === 'death' ) {
			$prop = 'P570';
			$value = '+' . $record['year_of_death'] . '-00-00T00:00:00Z/9';
		} else {
			return '';
		}
		if ( isset( $entity['claims'][$prop][0] ) ) {
			$value = $entity['claims'][$prop][0]['mainsnak']['datavalue']['value']['time']
				. '/'
				. $entity['claims'][$prop][0]['mainsnak']['datavalue']['value']['precision'];
		}
		$statedIn = 'S248';
		$wabmdItem = 'Q42333722';
		$pubDate = 'S577';
		$regNum = 'S958';
		return $entity['id'] . "|$prop|$value"
			. "|$statedIn|$wabmdItem"
			. "|$pubDate|+" . $record['registration_year'] . "-00-00T00:00:00Z/9"
			. "|$regNum|\"" . $record['registration_number'] . "\"";
	}

	/**
	 * Check that at least one of the references of one of the claims has the required fields.
	 */
	private function checkRef( ?array $claims = null ): bool {
		if ( !$claims ) {
			return false;
		}
		$wabmdItem = 'Q42333722';
		foreach ( $claims as $claim ) {
			foreach ( $claim['references'] ?? [] as $ref ) {
				$statedIn = $ref['snaks']['P248'][0]['datavalue']['value']['id'] ?? null;
				$pubDate = $ref['snaks']['P577'][0]['datavalue']['value']['time'] ?? null;
				$regNum = $ref['snaks']['P958'][0]['datavalue']['value'] ?? null;
				if ( $statedIn === $wabmdItem && $pubDate && $regNum ) {
					return true;
				}
			}
		}
		return false;
	}
}
