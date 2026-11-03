"""Cliente gRPC opcional en Python.

Requiere los stubs generados con:
    python -m grpc_tools.protoc -I. --python_out=. --grpc_python_out=. producto.proto
"""

import sys

import grpc

import producto_pb2
import producto_pb2_grpc

id_producto = int(sys.argv[1]) if len(sys.argv) > 1 else 1

with grpc.insecure_channel("127.0.0.1:50051") as canal:
    stub = producto_pb2_grpc.ProductoServiceStub(canal)

    try:
        print(stub.ObtenerProducto(producto_pb2.ProductoRequest(id=id_producto)))
    except grpc.RpcError as error:
        print(error.code(), error.details())
