<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Panel\Product;
use App\Repository\category\categoryRepo;
use App\Repository\products\productRepo;
use App\Repository\supportRepo\supportRepo;
use App\Services\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use function Symfony\Component\VarDumper\Dumper\esc;

class ProductController extends Controller
{
    public $productRepo;

    public function __construct(productRepo $productRepo)
    {
        $this->productRepo = $productRepo;
    }

    public function index()
    {
        return $products = $this->productRepo->index();
    }

    public function store_one(Request $request)
    {
        $products = $this->productRepo->create_one($request);
        return response()->json(['id' => $products->id], 200);
    }

    public function store_two(Request $request, $product, categoryRepo $categoryRepo, supportRepo $supportRepo)
    {
        $product = $this->productRepo->getFindId($product);

        $category = $categoryRepo->getById($request->category_id);

        $support = $supportRepo->getMultiId($request->support_id);
        $this->productRepo->create_two($request, $category, $product);
        $product->supports()->sync($support);
        return response()->json(['id' => $product->id], 200);
    }

    public function store_three(Request $request, $product)
    {
        $product = $this->productRepo->getFindId($product);
        $multi_image = $request->multi_image ? File::image($request->file('multi_image')) : null;
        // $multi_image = json_encode($multi_image);
        $multi_image_en = $request->multi_image_en ? File::image_en($request->file('multi_image_en')) : null;
        // $multi_image_en = json_encode($multi_image_en);
        $this->productRepo->create_three($request, $product, $multi_image, $multi_image_en);
        return response()->json(['id' => $product->id], 200);
    }

    public function store_four(Request $request, $product)
    {
        $product = $this->productRepo->getFindId($product);
        $video_url = $request->video_url ? File::video_peo($request->file('video_url')) : null;
        $video_url_en = $request->video_url_en ? File::video_peo_en($request->file('video_url_en')) : null;
        $this->productRepo->create_four($product, $video_url, $video_url_en);
        return response()->json(['id' => $product->id], 200);
    }

    public function show($product)
    {
        return $this->productRepo->findSupportId($product);
    }


    public function update_one(Request $request, $product, categoryRepo $categoryRepo, supportRepo $supportRepo)
    {

        $productId = $this->productRepo->getFindId($product);
        $category = $categoryRepo->getById($request->category_id);
        $support = $supportRepo->getMultiId($request->support_id);
        $this->productRepo->update_one($request, $productId, $category);
        $productId->supports()->sync($support);
        return response()->json(['id' => $productId->id], 200);
    }


    public function update_two(Request $request, $product, categoryRepo $categoryRepo, supportRepo $supportRepo)
    {
        $existingImages = null;
        $existingVideos = null;
        $product = $this->productRepo->getFindId($product);
        $oldImages = $request->oldImages;
        $basePath = "http://192.168.88.253:8003/images/products/fa/";
        $relativePathImage = str_replace($basePath, '', $oldImages);
        if (!empty($relativePathImage)) {
            $existingImages = Product::query()->where(function ($query) use ($relativePathImage) {
                foreach ($relativePathImage as $path) {
                     $query->orWhere('multi_image', 'LIKE', '%' . $path . '%');
                }
            })->first();
//            $productCheckId = Product::query()->where('id', $existingImages->id)->update(['multi_image' => $relativePathImage]);
        }
        $oldVideos = $request->oldVideos;
        $basePath = "http://192.168.88.253:8003/files/products/fa/";
        $relativePathVideo = str_replace($basePath, '', $oldVideos);
        if($relativePathVideo)
        {
             $existingVideos = Product::query()->where(function ($query) use ($relativePathVideo) {
                foreach ($relativePathVideo as $path) {
                     $query->orWhere('video_url', 'LIKE', '%' . $path . '%');
                }
            })->first();
        }

//        $productCheckIdVideo = Product::query()->where('id', $existingVideos->id)->update(['video_url' => $relativePathVideo]);
        if ($request->hasFile('multi_image')) {
            if (!empty($product->video_url) > 2) {
                foreach ($product->multi_image as $oldImage) {
                    $oldImagePath = public_path('images/products/fa/' . $oldImage);
                    if (\Illuminate\Support\Facades\File::exists($oldImagePath)) {
                        \Illuminate\Support\Facades\File::delete($oldImagePath);
                    }
                }
            }
            $multi_image = File::image($request->file('multi_image'));
        }
        $multi_image = $multi_image ?? $product->multi_image;
        if ($request->hasFile('video_url')) {

            if (!empty($product->video_url) > 2) {
                foreach ($product->video_url as $oldImage) {
                    if (Storage::delete('files/products/fa/' . $oldImage)) {
                        Storage::delete('files/products/fa/' . $oldImage);
                    }
                }
            }
            $video_url = $request->video_url ? File::video_peo($request->file('video_url')) : null;
        }
        $video_url = $video_url ?? $product->video_url;
        $this->productRepo->update_two($request, $product, $multi_image, $video_url , $existingImages  , $existingVideos );
        return response()->json(['id' => $product->id], 200);
    }

    public function update_three(Request $request, $product)
    {
        $existingImages = null;
        $existingVideos = null;
        $product = $this->productRepo->getFindId($product);
        $oldImages = $request->oldImages_en;
        $basePath = "http://192.168.88.253:8003/images/products/en/";
        $relativePathImage = str_replace($basePath, '', $oldImages);
        if (!empty($relativePathImage)) {
            $existingImages = Product::query()->where(function ($query) use ($relativePathImage) {
                foreach ($relativePathImage as $path) {
                    $query->orWhere('multi_image_en', 'LIKE', '%' . $path . '%');
                }
            })->first();
//            $productCheckId = Product::query()->where('id', $existingImages->id)->update(['multi_image' => $relativePathImage]);
        }
        $oldVideos = $request->oldVideos_en;
        $basePath = "http://192.168.88.253:8003/files/products/en/";
        $relativePathVideo = str_replace($basePath, '', $oldVideos);
        if($relativePathVideo)
        {
            $existingVideos = Product::query()->where(function ($query) use ($relativePathVideo) {
                foreach ($relativePathVideo as $path) {
                    $query->orWhere('video_url_en', 'LIKE', '%' . $path . '%');
                }
            })->first();
        }

        if ($request->hasFile('multi_image_en')) {
            if (!empty($product->multi_image_en)) {
                foreach ($product->multi_image_en as $oldImage) {
                    $oldImagePath = public_path('images/products/en/' . $oldImage);
                    if (\Illuminate\Support\Facades\File::exists($oldImagePath)) {
                        \Illuminate\Support\Facades\File::delete($oldImagePath);
                    }
                }
            }
            $multi_image_en = $request->multi_image_en ? File::image_en($request->file('multi_image_en')) : null;
        }
        $multi_image_en = $multi_image_en ?? $product->multi_image_en;


        if ($request->hasFile('video_url_en')) {
            if (!empty($product->video_url_en)) {
                foreach ($product->video_url_en as $oldImage) {
                    if (Storage::delete('files/products/en/' . $oldImage)) {
                        Storage::delete('files/products/en/' . $oldImage);
                    }
                }
            }
            $video_url_en = $request->video_url_en ? File::video_peo_en($request->file('video_url_en')) : null;
        }
        $video_url_en = $video_url_en ?? $product->video_url_en;

        $this->productRepo->update_three($request, $product, $multi_image_en, $video_url_en , $existingImages  , $existingVideos);
        return response()->json(['id' => $product->id], 200);
    }
    // public function update_four(Request $request, $product)
    // {
    //     $product = $this->productRepo->getFindId($product);
    //     $video_url = $request->video_url ? File::video_peo($request->file('video_url')) : null;
    //     $video_url_en = $request->video_url_en ? File::video_peo_en($request->file('video_url_en')) : null;
    //     $this->productRepo->update_four($product, $video_url, $video_url_en);
    //     return response()->json(['id' => $product->id], 200);
    // }
    public function destroy($product)
    {
        $product = $this->productRepo->getFindId($product);
        if (!empty($product->multi_image)) {
            foreach ($product->multi_image as $oldImage) {
                $oldImagePath = public_path('images/products/fa/' . $oldImage);
                if (\Illuminate\Support\Facades\File::exists($oldImagePath)) {
                    \Illuminate\Support\Facades\File::delete($oldImagePath);
                }
            }
        }
        if (!empty($product->multi_image_en)) {
            foreach ($product->multi_image_en as $oldImage) {
                $oldImagePath = public_path('images/products/en/' . $oldImage);
                if (\Illuminate\Support\Facades\File::exists($oldImagePath)) {
                    \Illuminate\Support\Facades\File::delete($oldImagePath);
                }
            }
        }
        if (!empty($product->video_url)) {
            foreach ($product->video_url as $oldImage) {
                if (Storage::delete('files/products/fa/' . $oldImage)) {
                    Storage::delete('files/products/fa/' . $oldImage);
                }
            }
        }
        if (!empty($product->video_url_en)) {
            foreach ($product->video_url_en as $oldImage) {
                $path = 'files/products/en/' . $oldImage;
                if (Storage::delete($path)) {
                    Storage::delete($path);
                }
            }
        }
        $this->productRepo->delete($product);
        return response()->json(['ok'], 200);
    }

    public function price_status_enable($id)
    {
        $this->productRepo->status($id, Product::STATUS_PRICE_ANSIBLE);
        return response()->json(["status" => "success", 'message' => "success change status price"], 200);
    }

    public function price_status_disable($id, $status)
    {

        if ($status == "1") {
            $this->productRepo->status($id, Product::STATUS_PRICE_ANSIBLE);
            return response()->json(["status" => "success", 'message' => "success change status enable price"], 200);
        } else {
            if ($status == "0") {
                $this->productRepo->status($id, Product::STATUS_PRICE_DISABLE);
                return response()->json(["status" => "success", 'message' => "success change status disable price"], 200);
            } else {
                return response()->json(["status" => "failed", 'message' => "failed"], 405);
            }
        }

    }
}
