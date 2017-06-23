<?php
/**
 * Cart repository.
 */
namespace Repository;

use Doctrine\DBAL\Connection;
use Repository\ProductRepository;
use Silex\Application;


/**
 * Class CartRepository
 *.
 *
 * @package Repository
 */
class CartRepository
{
    /**
     * Doctrine DBAL connection.
     *
     * @var \Doctrine\DBAL\Connection $db
     */
    protected $db;

    /**
     * CartRepository constructor.
     *
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Find one record.
     *
     * @param string $id Element id
     *
     * @return array|mixed Result
     */
    public function findAllData(Application $app, $cart)
    {
        $productRepository = new ProductRepository($app['db']);
        $cartData = [];

        foreach($cart as $product){
            $id = $product['product_id'];
            $qty = $product['qty'];
            $productData = $productRepository->findOneById($id);
            array_push($cartData, [
                    'product_id' => $id,
                    'name' => $productData['name'],
                    'img' => $productData['img'],
                    'price' => $productData['price'],
                    'qty' => $qty
                ]
            );
        }
        return $cartData;

    }

    /**
     * @param Application $app
     * @param $borrow_data from BorrowType
     * @param $cart
     */
    public function borrow(Application $app, $borrow_data, $cart)
    {
        $userRepository = new UserRepository($app['db']);
        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $username = $token->getUser()->getUsername();
        }
        $user_data = $userRepository->getUserByLogin($username);
        $user_id = $user_data['user_id'];

        $borrowed = [
            'FK_user_id' => $user_id,
            'date' => date('Y-m-d'),
            'order_price' => $borrow_data['order_price'],
            'from' => $borrow_data['from'],
            'to' => $borrow_data['to'],
        ];

        $this->db->beginTransaction();
        $this->db->insert('borrowed', $borrowed);
        $borrowedId = $this->db->lastInsertId();
        foreach($cart as $product){
            $addProduct = [
                'FK_borrowed_id' => $borrowedId,
                'FK_product_id' => $product['product_id'],
                'qty' => $product['qty'],
            ];
            $this->db->insert('borrowed_data', $addProduct);
        }

        return $this->db->commit();
    }


}