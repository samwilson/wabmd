<?php

namespace App\Controller;

use App\Database;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController {

	private $db;

	public function __construct( Database $database ) {
		$this->db = $database;
	}

	#[Route( '/', name: 'home' )]
	public function index( Request $request ): Response {
		if ( $request->get( 'type' ) ) {
			return $this->redirectToRoute( 'record', [
				'type' => $request->get( 'type' ),
				'year' => $request->get( 'registration_year' ),
				'num' => $request->get( 'registration_number' ),
			] );
		}
		return $this->render( 'home.html.twig', [
			'total_count' => $this->db->getTotalRecords(),
			'total_wikidata' => $this->db->getTotalWikidata(),
			'total_wikitree' => $this->db->getTotalWikiTree(),
			'year_totals' => $this->db->getYearTotals(),
		] );
	}

	#[Route( '/{type}/{year}', name: 'year',
		requirements: [ 'type' => '(births|marriages|deaths)', 'year' => '\d{4}' ]
	)]
	public function year( string $type, string $year ): Response {
		$data = $this->db->getYearData( $type, $year );
		if ( !$data ) {
			throw $this->createNotFoundException();
		}
		return $this->render( 'records.html.twig', [
			'type' => $type,
			'type_singular' => substr( $type, 0, -1 ),
			'title' => $year,
			'data' => $data,
		] );
	}

	#[Route( '/people', name: 'people' )]
	public function people( Request $request ): Response {
		$q = trim( $request->get( 'q' ) );
		if ( strlen( $q ) < 5 ) {
			$this->addFlash( 'warning', "Please search people's names by using at least five letters." );
			return $this->redirectToRoute( 'home' );
		}
		return $this->render( 'people.html.twig', [
			'q' => $q,
			'data' => $this->db->searchPeople( $q ),
		] );
	}

	#[Route( '/places', name: 'places' )]
	public function places( Request $request ): Response {
		$q = trim( $request->get( 'q' ) );
		if ( strlen( $q ) < 3 ) {
			$this->addFlash( 'warning', 'Please search place names by using at least three letters.' );
			return $this->redirectToRoute( 'home' );
		}
		$birthPlaces = $this->db->searchPlaces( 'births', $q );
		$data = [];
		foreach ( $birthPlaces as $place ) {
			$data[$place['id']] = [
				'id' => $place['id'],
				'title' => $place['title'],
				'births' => $place['count'],
				'deaths' => 0,
			];
		}
		$deathPlaces = $this->db->searchPlaces( 'deaths', $q );
		foreach ( $deathPlaces as $place ) {
			if ( !isset( $data[ $place['id'] ] ) ) {
				$data[$place['id']] = [
					'id' => $place['id'],
					'title' => $place['title'],
					'births' => 0,
				];
			}
			$data[$place['id']]['deaths'] = $place['count'];
		}
		return $this->render( 'places.html.twig', [
			'q' => $q,
			'data' => $data
		] );
	}

	#[Route( '/places/{placeId}/{type}', name: 'place_records',
		requirements: [ 'type' => '(births|marriages|deaths)', 'placeId' => '\d+' ]
	)]
	public function placeRecords( Request $request, int $placeId, string $type ): Response {
		$title = $this->db->getPlaceTitle( $placeId );
		$data = $this->db->getPlaceData( $type, $placeId );
		return $this->render( 'records.html.twig', [
			'type' => $type,
			'type_singular' => substr( $type, 0, -1 ),
			'title' => $title,
			'data' => $data,
		] );
	}
}
