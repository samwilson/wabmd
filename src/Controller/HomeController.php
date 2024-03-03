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
			'year_totals' => $this->db->getYearTotals(),
		] );
	}

	#[Route( '/{type}/{year}', name: 'year' )]
	public function year( string $type, string $year ): Response {
		$year = $this->db->getYear( $type, $year );
		if ( !$year ) {
			throw $this->createNotFoundException();
		}
		return $this->render( 'year.html.twig', [
			'total_count' => $this->db->getTotalRecords(),
			'type' => $type,
			'year' => $year,
		] );
	}

	#[Route( '/{type}/{year}/{num}', name: 'record' )]
	public function record( string $type, string $year, string $num ): Response {
		$record = $this->db->getRecord( $type, $year, $num );
		if ( !$record ) {
			throw $this->createNotFoundException();
		}
		return $this->render( 'record.html.twig', [
			'total_count' => $this->db->getTotalRecords(),
			'type' => $type,
			'record' => $record,
		] );
	}
}
